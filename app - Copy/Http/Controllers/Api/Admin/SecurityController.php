<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Security;
use App\Models\PendingSecurity;
use App\Services\SecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SecurityController extends Controller
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    #[OA\Get(
        path: "/api/v1/admin/securities",
        operationId: "getSecurities",
        summary: "Get List of Securities",
        tags: ["Securities Master List"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Number of items per page",
                required: false,
                schema: new OA\Schema(type: "integer", default: 15)
            )
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
  
        $perPage = $request->get('per_page', 15);
        $securities = $this->securityService->getAllSecurities($perPage);
        return response()->json($securities);
    }

    #[OA\Post(
        path: "/api/v1/admin/securities",
        operationId: "createSecurity",
        summary: "Create Security (Manual Upload)",
        description: "Submit a request to create a new security record, selecting an authoriser.",
        tags: ["Securities Master List"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["requested_by", "authoriser_id"],
                properties: [
                    new OA\Property(property: "issue_category", type: "string"),
                    new OA\Property(property: "issuer", type: "string"),
                    new OA\Property(property: "security_type_id", type: "integer", description: "Foreign key to security_types table"),
                    new OA\Property(property: "isin", type: "string", maxLength: 12),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "issue_date", type: "string", format: "date"),
                    new OA\Property(property: "maturity_date", type: "string", format: "date"),
                    new OA\Property(property: "coupon", type: "number", format: "float"),
                    new OA\Property(property: "coupon_type", type: "string", enum: ["Fixed", "Floating"]),
                    new OA\Property(property: "frm", type: "number", format: "float", description: "Floating Rate Margin (required if coupon_type=Floating)"),
                    new OA\Property(property: "frb", type: "string", description: "Floating Rate Benchmark (required if coupon_type=Floating)"),
                    new OA\Property(property: "frbv", type: "number", format: "float", description: "Floating Rate Benchmark Value (required if coupon_type=Floating)"),
                    new OA\Property(property: "coupon_floor", type: "number", format: "float", description: "Coupon Floor (required if coupon_type=Floating)"),
                    new OA\Property(property: "coupon_cap", type: "number", format: "float", description: "Coupon Cap (required if coupon_type=Floating)"),
                    new OA\Property(property: "coupon_frequency", type: "integer"),
                    new OA\Property(property: "fgn_benchmark_yield", type: "number", format: "float"),
                    new OA\Property(property: "issue_size", type: "number", format: "float"),
                    new OA\Property(property: "outstanding_value", type: "number", format: "float"),
                    new OA\Property(property: "day_count_convention", type: "string", enum: ["US (NASD) 30/360", "Actual/Actual", "Actual/360", "Actual/365", "European 30/360"]),
                    new OA\Property(property: "option_type", type: "string", enum: ["Option-Free", "Amortising", "Callable"]),
                    new OA\Property(property: "call_date", type: "string", format: "date", description: "Required if option_type=Callable"),
                    new OA\Property(property: "yield_at_issue", type: "string"),
                    new OA\Property(property: "interest_determination_date", type: "string", format: "date"),
                    new OA\Property(property: "listing_status", type: "string", enum: ["Fully Listed (FL)", "Permitted Trading (PT)"]),
                    new OA\Property(property: "rating_1_agency", type: "string"),
                    new OA\Property(property: "rating_1", type: "string"),
                    new OA\Property(property: "rating_1_issuance_date", type: "string", format: "date"),
                    new OA\Property(property: "rating_1_expiration_date", type: "string", format: "date"),
                    new OA\Property(property: "rating_2_agency", type: "string"),
                    new OA\Property(property: "rating_2", type: "string"),
                    new OA\Property(property: "rating_2_issuance_date", type: "string", format: "date"),
                    new OA\Property(property: "rating_2_expiration_date", type: "string", format: "date"),
                    new OA\Property(property: "product_type_id", type: "integer"),
                    new OA\Property(property: "requested_by", type: "integer", description: "The ID of the user requesting this action (Inputter)"),
                    new OA\Property(property: "authoriser_id", type: "integer", description: "The ID of the authoriser who should approve this request"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201, 
                description: "Request created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Security creation request submitted for approval."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'issue_category' => 'nullable',
            'issuer' => 'nullable',
            'security_type_id' => 'nullable|exists:security_types,id',
            'isin' => 'nullable',
            'description' => 'nullable',
            'issue_date' => 'nullable',
            'maturity_date' => 'nullable',
            'tenor' => 'nullable',
            'coupon' => 'nullable',
            'coupon_type' => 'nullable',
            'frm' => 'nullable',
            'frb' => 'nullable',
            'frbv' => 'nullable',
            'coupon_floor' => 'nullable',
            'coupon_cap' => 'nullable',
            'coupon_frequency' => 'nullable',
            'effective_coupon' => 'nullable',
            'fgn_benchmark_yield' => 'nullable',
            'issue_size' => 'nullable',
            'outstanding_value' => 'nullable',
            'ttm' => 'nullable',
            'day_count_convention' => 'nullable',
            'day_count_basis' => 'nullable',
            'option_type' => 'nullable',
            'call_date' => 'nullable',
            'yield_at_issue' => 'nullable',
            'interest_determination_date' => 'nullable',
            'listing_status' => 'nullable',
            'rating_1_agency' => 'nullable',
            'rating_1' => 'nullable',
            'rating_1_issuance_date' => 'nullable',
            'rating_1_expiration_date' => 'nullable',
            'rating_2_agency' => 'nullable',
            'rating_2' => 'nullable',
            'rating_2_issuance_date' => 'nullable',
            'rating_2_expiration_date' => 'nullable',
            'final_rating' => 'nullable',
            'product_type_id' => 'nullable|exists:product_types,id',
            'requested_by' => 'required',
            'authoriser_id' => 'required',
        ]);


          $existing = PendingSecurity::where('name', $request->name)
            ->orWhere('code', $request->code)
            ->first();

        if ($existing) {
             return response()->json([
                'message' => 'Security with this name or code already exists.',
                'error_code' => 'DUPLICATE_ENTRY'
            ], 400);
        }

        try {
            $pending = $this->securityService->createRequest($validated);
            return response()->json([
                'message' => 'Security creation request submitted for approval.',
                'data' => $pending
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/admin/securities/import",
        operationId: "importSecurities",
        summary: "Bulk Import Securities",
        description: "Upload a CSV/Excel file to create multiple security requests.",
        tags: ["Securities Master List"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["file", "requested_by", "authoriser_id"],
                    properties: [
                        new OA\Property(property: "file", type: "string", format: "binary"),
                        new OA\Property(property: "requested_by", type: "integer"),
                        new OA\Property(property: "authoriser_id", type: "integer"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Import queued successfully"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
            'requested_by' => 'required|exists:users,id',
            'authoriser_id' => 'required|exists:users,id',
        ]);

        try {
            $import = new \App\Imports\SecuritiesImport(
                $this->securityService, 
                $request->requested_by, 
                $request->authoriser_id
            );
            
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            return response()->json([
                'message' => 'Bulk import processed. Requests have been submitted for approval.',
                'info' => 'Check the logs at storage/logs/laravel.log for detailed import results.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Import failed: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/admin/securities/{security}",
        operationId: "getSecurity",
        summary: "Get Security Details",
        tags: ["Securities Master List"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "security",
                in: "path",
                description: "Security ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function show(Security $security): JsonResponse
    {
        $security = $this->securityService->getSecurity($security);
        return response()->json($security);
    }

    #[OA\Put(
        path: "/api/v1/admin/securities/{security}",
        operationId: "updateSecurity",
        summary: "Update Security (Request)",
        description: "Submit a request to update an existing security, selecting an authoriser.",
        tags: ["Securities Master List"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "security",
                in: "path",
                description: "Security ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["requested_by", "authoriser_id"],
                properties: [
                    new OA\Property(property: "issue_category", type: "string"),
                    new OA\Property(property: "issuer", type: "string"),
                    new OA\Property(property: "security_type", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "requested_by", type: "integer"),
                    new OA\Property(property: "authoriser_id", type: "integer"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200, 
                description: "Request created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Security update request submitted for approval."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function update(Request $request, Security $security): JsonResponse
    {
        $validated = $request->validate([
            'issue_category' => 'nullable',
            'issuer' => 'nullable',
            'security_type_id' => 'nullable|exists:security_types,id',
            'isin' => 'nullable',
            'description' => 'nullable',
            'issue_date' => 'nullable',
            'maturity_date' => 'nullable',
            'tenor' => 'nullable',
            'coupon' => 'nullable',
            'coupon_type' => 'nullable',
            'frm' => 'nullable',
            'frb' => 'nullable',
            'frbv' => 'nullable',
            'coupon_floor' => 'nullable',
            'coupon_cap' => 'nullable',
            'coupon_frequency' => 'nullable',
            'effective_coupon' => 'nullable',
            'fgn_benchmark_yield' => 'nullable',
            'issue_size' => 'nullable',
            'outstanding_value' => 'nullable',
            'ttm' => 'nullable',
            'day_count_convention' => 'nullable',
            'day_count_basis' => 'nullable',
            'option_type' => 'nullable',
            'call_date' => 'nullable',
            'yield_at_issue' => 'nullable',
            'interest_determination_date' => 'nullable',
            'listing_status' => 'nullable',
            'rating_1_agency' => 'nullable',
            'rating_1' => 'nullable',
            'rating_1_issuance_date' => 'nullable',
            'rating_1_expiration_date' => 'nullable',
            'rating_2_agency' => 'nullable',
            'rating_2' => 'nullable',
            'rating_2_issuance_date' => 'nullable',
            'rating_2_expiration_date' => 'nullable',
            'final_rating' => 'nullable',
            'product_type_id' => 'nullable|exists:product_types,id',
            'requested_by' => 'required',
            'authoriser_id' => 'required',
        ]);


          if ($request->filled('name') || $request->filled('isin')) {
        $existing = PendingSecurity::query()
            ->where(function ($query) use ($request) {
                if ($request->filled('name')) {
                    $query->where('name', $request->name);
                }

                if ($request->filled('isin')) {
                    $query->orWhere('isin', $request->isin);
                }
            })
            ->where('id', '!=', $security->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message'    => 'Security with this name or ISIN already exists.',
                'error_code' => 'DUPLICATE_ENTRY',
            ], 409);
        }
    }

        try {
            $pending = $this->securityService->updateRequest($security, $validated);
            return response()->json([
                'message' => 'Security update request submitted for approval.',
                'data' => $pending
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    #[OA\Delete(
        path: "/api/v1/admin/securities/{security}",
        operationId: "deleteSecurity",
        summary: "Delete Security (Request)",
        description: "Submit a request to delete a security, selecting an authoriser.",
        tags: ["Securities Master List"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "security",
                in: "path",
                description: "Security ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "authoriser_id",
                in: "query",
                description: "The ID of the authoriser who should approve this request",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "requested_by",
                in: "query",
                description: "The ID of the user requesting this action (Inputter)",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Request created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Security deletion request submitted for approval."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function destroy(Request $request, Security $security): JsonResponse
    {
        $validated = $request->validate([
            'authoriser_id' => 'required|exists:users,id',
            'requested_by'  => 'required|exists:users,id',
        ]);

        try {
            $pending = $this->securityService->deleteRequest($security, $validated);
            return response()->json([
                'message' => 'Security deletion request submitted for approval.',
                'data' => $pending
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // Pending Requests

    #[OA\Get(
        path: "/api/v1/admin/pending-securities",
        operationId: "getPendingSecurities",
        summary: "Get Pending Securities",
        description: "Retrieve a paginated list of pending security requests.",
        tags: ["Security Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Number of items per page",
                required: false,
                schema: new OA\Schema(type: "integer", default: 15)
            )
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function pending(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $pending = $this->securityService->getPendingRequests($perPage);
        return response()->json($pending);
    }

    #[OA\Post(
        path: "/api/v1/admin/pending-securities/{pendingSecurity}/approve",
        operationId: "approveSecurity",
        summary: "Approve Pending Security",
        description: "Approve a pending security request.",
        tags: ["Security Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "pendingSecurity",
                in: "path",
                description: "Pending Security ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Approved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Request approved successfully."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function approve(PendingSecurity $pendingSecurity): JsonResponse
    {
        try {
            $result = $this->securityService->approveRequest($pendingSecurity);
            return response()->json([
                'message' => 'Request approved successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    #[OA\Post(
        path: "/api/v1/admin/pending-securities/{pendingSecurity}/reject",
        operationId: "rejectSecurity",
        summary: "Reject Pending Security",
        description: "Reject a pending security request.",
        tags: ["Security Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "pendingSecurity",
                in: "path",
                description: "Pending Security ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["reason"],
                properties: [
                    new OA\Property(property: "reason", type: "string")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200, 
                description: "Rejected successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Request rejected successfully."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function reject(Request $request, PendingSecurity $pendingSecurity): JsonResponse
    {
        $validated = $request->validate(['reason' => 'required|string']);
        
        try {
            $result = $this->securityService->rejectRequest($pendingSecurity, $validated['reason']);
            return response()->json([
                'message' => 'Request rejected successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
