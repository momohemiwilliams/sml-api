<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionResult;
use App\Models\PendingAuctionResult;
use App\Services\AuctionResultService;
use App\Services\ExternalUserService;
use App\Services\AuctionResultActivityLogService;
use App\Http\Requests\StoreAuctionResultRequest;
use App\Http\Requests\UpdateAuctionResultRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuctionResultController extends Controller
{
    protected $service;
    protected $userService;
    protected $activityLogService;

    public function __construct(AuctionResultService $service, ExternalUserService $userService, AuctionResultActivityLogService $activityLogService)
    {
        $this->service = $service;
        $this->userService = $userService;
        $this->activityLogService = $activityLogService;
    }

    // LIST EXISTING
    public function index(Request $request)
    {
        $query = AuctionResult::with(['security.fieldValues', 'security.productType']);

        $this->activityLogService->logView('List of auction results viewed', [
            'security_id' => $request->security_id,
            'auction_date' => $request->auction_date
        ]);

        if ($request->filled('security_id')) {
            $query->where('security_id', $request->security_id);
        }
        
        if ($request->filled('auction_date')) {
            $query->whereDate('auction_date', $request->auction_date);
        }

        $results = $query->latest('auction_date')->paginate(15);
        $this->userService->enrichWithUsers($results, ['created_by' => 'creator', 'authoriser_id' => 'authoriser']);

        $results->getCollection()->transform(function ($item) {
            $item->security_name = $item->security->security_name ?? null;
            $item->product_name = $item->security->productType->name ?? null;
            $item->makeHidden(['security']);
            return $item;
        });

        return response()->json($results);
    }

    // CREATE REQUEST
    public function store(StoreAuctionResultRequest $request)
    {
        $data = $request->validated();
        // Service expects created_by
        $data['created_by'] = $data['requested_by'] ?? auth()->id();
        
        try {
            $pending = $this->service->createRequest($data);
            $this->activityLogService->logAuctionResultCreation($pending, $data);
            
            return response()->json([
                'message' => 'Auction Result creation submitted for approval.',
                'data' => $pending
            ], 202);
        } catch (\Exception $e) {
            $this->activityLogService->logView('Auction Result creation failed', [
                'user_id' => $data['created_by'] ?? null,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // SHOW EXISTING
    public function show(AuctionResult $auctionResult)
    {
        $this->activityLogService->logView('Auction Result details viewed', [
            'auction_result_id' => $auctionResult->id
        ]);
        $auctionResult->load(['security.fieldValues', 'security.productType', 'creator']);
        $auctionResult->security_name = $auctionResult->security->security_name ?? null;
        $auctionResult->product_name = $auctionResult->security->productType->name ?? null;
        $auctionResult->makeHidden(['security']);
        return response()->json($auctionResult);
    }

    // UPDATE REQUEST
    public function update(UpdateAuctionResultRequest $request, AuctionResult $auctionResult)
    {
        $data = $request->validated();
        $data['updated_by'] = $data['requested_by'] ?? auth()->id();
        $data['authoriser_id'] = $request->authoriser_id;

        try {
            $pending = $this->service->updateRequest($auctionResult, $data);
            $this->activityLogService->logAuctionResultUpdate($auctionResult, $data);
            
            return response()->json([
                'message' => 'Auction Result update submitted for approval.',
                'data' => $pending
            ], 202);
        } catch (\Exception $e) {
            $this->activityLogService->logView('Auction Result update failed', [
                'user_id' => $data['updated_by'] ?? null,
                'auction_result_id' => $auctionResult->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // DELETE REQUEST
    public function destroy(Request $request, AuctionResult $auctionResult)
    {
        $validated = $request->validate([
            'updated_by' => 'required',
            'authoriser_id' => 'required',
        ]);

        try {
            $pending = $this->service->deleteRequest($auctionResult, $validated);
            $this->activityLogService->logAuctionResultDeletion($auctionResult, $validated);
            
            return response()->json([
                'message' => 'Auction Result deletion submitted for approval.',
                'data' => $pending
            ], 202);
        } catch (\Exception $e) {
            $this->activityLogService->logView('Auction Result deletion failed', [
                'user_id' => $validated['updated_by'] ?? null,
                'auction_result_id' => $auctionResult->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // BULK UPLOAD
    #[OA\Post(
        path: "/api/v1/admin/auction-results/bulk-upload",
        operationId: "bulkUploadAuctionResults",
        summary: "Bulk Upload Auction Results",
        tags: ["Auction Results"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["file", "created_by", "authoriser_id"],
                    properties: [
                        new OA\Property(property: "file", type: "string", format: "binary"),
                        new OA\Property(property: "created_by", type: "integer"),
                        new OA\Property(property: "authoriser_id", type: "integer")
                    ]
                )
            )
        ),
        responses: [new OA\Response(response: 200, description: "Uploaded successfully")]
    )]
    public function bulkUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'created_by' => 'required|integer',
            'authoriser_id' => 'required|integer',
        ]);

        try {
            $this->activityLogService->logBulkUpload([
                'created_by' => $request->created_by,
                'authoriser_id' => $request->authoriser_id
            ]);
            
            $import = new \App\Imports\AuctionResultImport(
                $this->service,
                $request->created_by,
                $request->authoriser_id
            );

            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            $errors = $import->getErrors();
            $failures = $import->failures();
            
            foreach ($failures as $failure) {
                $row = $failure->row();
                $attribute = $failure->attribute();
                $messages = implode(', ', $failure->errors());
                $errors[] = "Row {$row} [{$attribute}]: {$messages}";
            }

            $counts = $import->getCounts();

            $response = [
                'success_count' => $counts['success'],
                'error_count' => $counts['errors'] + count($failures),
                'errors' => $errors,
            ];

            if (empty($errors)) {
                $response['message'] = 'Bulk upload processed. Valid records submitted for approval.';
                return response()->json($response);  
            }

            $response['message'] = 'Upload failed due to validation errors. Please check the errors below.';
            $this->activityLogService->logView('Auction Result bulk upload validation failed', [
                'user_id' => $request->created_by,
                'error_count' => count($errors)
            ]);
            return response()->json($response, 422); 
        } catch (\Exception $e) {
            $this->activityLogService->logView('Auction Result bulk upload failed', [
                'user_id' => $request->created_by,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    // PENDING REQUESTS
    public function pending(Request $request)
    {
        $this->activityLogService->logView('Pending auction results viewed', [
            'per_page' => $request->get('per_page', 15)
        ]);
        $results = $this->service->getPendingRequests($request->get('per_page', 15));
        
        $results->getCollection()->transform(function ($item) {
            $item->security_name = $item->security->security_name ?? null;
            $item->product_name = $item->security->productType->name ?? null;
            $item->makeHidden(['security']);
            return $item;
        });

        return response()->json($results);
    }

    public function showPending(PendingAuctionResult $pendingAuctionResult)
    {
        $this->activityLogService->logView('Pending auction result details viewed', [
            'pending_id' => $pendingAuctionResult->id
        ]);
        $pendingAuctionResult->load(['security.fieldValues', 'security.productType', 'requester', 'mainRecord']);
        $pendingAuctionResult->security_name = $pendingAuctionResult->security->security_name ?? null;
        $pendingAuctionResult->product_name = $pendingAuctionResult->security->productType->name ?? null;
        $pendingAuctionResult->makeHidden(['security']);

        return response()->json($pendingAuctionResult);
    }

    public function approve(PendingAuctionResult $pendingAuctionResult)
    {
        try {
            $result = $this->service->approveRequest($pendingAuctionResult);
            $this->activityLogService->logApproval($pendingAuctionResult, $pendingAuctionResult->authoriser_id);
            return response()->json(['message' => 'Request approved successfully.', 'data' => $result]);
        } catch (\Exception $e) {
            $this->activityLogService->logView('Auction Result approval failed', [
                'pending_id' => $pendingAuctionResult->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function reject(Request $request, PendingAuctionResult $pendingAuctionResult)
    {
        $request->validate(['reason' => 'required|string']);
        
        try {
            $result = $this->service->rejectRequest($pendingAuctionResult, $request->reason);
            $this->activityLogService->logRejection($pendingAuctionResult, $pendingAuctionResult->authoriser_id, $request->reason);
            return response()->json(['message' => 'Request rejected successfully.', 'data' => $result]);
        } catch (\Exception $e) {
            $this->activityLogService->logView('Auction Result rejection failed', [
                'pending_id' => $pendingAuctionResult->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function getRecordsByDate(Request $request, $date = null)
    {
        $targetDate = $date ?? $request->input('auction_date');

        if (!$targetDate) {
            return response()->json([
                'success' => false,
                'message' => 'An auction date is required.'
            ], 422);
        }

        $auctionResults = AuctionResult::with(['security.fieldValues', 'security.productType'])
            ->whereDate('auction_date', $targetDate)
            ->get();

        $formattedResults = $auctionResults->map(function ($item) {
            $item->makeHidden([
                'approval_status',
                'remarks',
                'created_by',
                'updated_by',
                'approved_by',
                'approved_at',
                'created_at',
                'updated_at',
                'deleted_at'
            ]);

            $array = $item->toArray();
            
            // Replace the full security relation with just the security_name
            if (array_key_exists('security', $array) && $item->security) {
                $array['security'] = [
                    'security_name' => $item->security->security_name
                ];

                if ($item->security->productType) {
                    $array['product_type'] = [
                        'name' => $item->security->productType->name
                    ];
                }
            }
            
            return $array;
        });

        return response()->json([
            'success' => true,
            'data' => $formattedResults
        ]);
    }
}

