<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PendingSecurityManagement;
use App\Models\SecurityManagement;
use Illuminate\Http\Request;

class SecurityManagementController extends Controller
{
    protected $securityManagementService;

    public function __construct(\App\Services\SecurityManagementService $securityManagementService)
    {
        $this->securityManagementService = $securityManagementService;
    }

   
    public function store(Request $request)
    {
     
        
        $validated = $request->validate([
            'category_id' => 'required|exists:market_categories,id',
            'product_id' => 'nullable',
            'created_by' => 'required',
            'authoriser_id' => 'required',
            'fields' => 'required|array|min:1',
            'fields.*.field_name' => 'required|string|max:255',
            'fields.*.field_type' => 'required|in:Float,Decimal,Int,Text,File,Date,DateTime',
            'fields.*.required' => 'boolean',
        ]);

        try {
            $pendingRecords = [];

            foreach ($validated['fields'] as $field) {
                $pendingRecords[] = $this->securityManagementService->createRequest([
                    'category_id' => $validated['category_id'],
                    'product_id' => $validated['product_id'] ?? null,
                    'field_name' => $field['field_name'],
                    'field_type' => $field['field_type'],
                    'required' => $field['required'] ?? false,
                    'status' => true,
                    'created_by' => $validated['created_by'],
                    'authoriser_id' => $validated['authoriser_id'],
                ]);
            }

            return response()->json([
                'message' => count($pendingRecords) . ' security management field(s) submitted for approval.',
                'data' => $pendingRecords
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    
    public function storeDirect(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:market_categories,id',
            'product_id' => 'nullable',
            'fields' => 'required|array|min:1',
            'fields.*.field_name' => 'required|string|max:255',
            'fields.*.field_type' => 'required|in:Float,Decimal,Int,Text,File,Date,DateTime',
            'fields.*.required' => 'boolean',
        ]);

        try {
            $createdRecords = [];

            foreach ($validated['fields'] as $field) {
                $createdRecords[] = $this->securityManagementService->store([
                    'category_id' => $validated['category_id'],
                    'product_id' => $validated['product_id'] ?? null,
                    'field_name' => $field['field_name'],
                    'field_type' => $field['field_type'],
                    'required' => $field['required'] ?? false,
                    'status' => true,
                ]);
            }

            return response()->json([
                'message' => count($createdRecords) . ' security management field(s) created successfully.',
                'data' => $createdRecords
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all security management fields (from the main table)
     *
     * @OA\Get(
     *     path="/api/v1/admin/security-management",
     *     summary="List Security Management Fields",
     *     tags={"Security Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of fields")
     * )
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $fields = SecurityManagement::with(['category', 'productType'])
            ->latest()
            ->paginate($perPage);

        return response()->json($fields);
    }

    /**
     * Get a specific security management field
     *
     * @OA\Get(
     *     path="/api/v1/admin/security-management/{id}",
     *     summary="Get Security Management Field",
     *     tags={"Security Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Field details")
     * )
     */
    public function show($id)
    {
        $field = SecurityManagement::with(['category', 'productType'])->findOrFail($id);
        return response()->json($field);
    }

    /**
     * Update a security management field (Request)
     *
     * @OA\Put(
     *     path="/api/v1/admin/security-management/{id}",
     *     summary="Update Security Management Field (Request)",
     *     description="Submit a request to update an existing security management field, selecting an authoriser.",
     *     tags={"Security Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"requested_by", "authoriser_id"},
     *             @OA\Property(property="product_id", type="integer", nullable=true),
     *             @OA\Property(property="field_name", type="string"),
     *             @OA\Property(property="field_type", type="string", enum={"Float", "Decimal", "Int", "Text"}),
     *             @OA\Property(property="required", type="boolean"),
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="requested_by", type="integer", description="The ID of the inputter requesting this update"),
     *             @OA\Property(property="authoriser_id", type="integer", description="The ID of the authoriser who should approve this request")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Request created successfully")
     * )
     */
    public function update(Request $request, $id)
    {
        $field = SecurityManagement::findOrFail($id);

        $validated = $request->validate([
            'product_id' => 'nullable',
            'field_name' => 'nullable|string|max:255',
            'field_type' => 'nullable|in:Float,Decimal,Int,Text,File,Date,DateTime',
            'required' => 'nullable|boolean',
            'status' => 'nullable|boolean',
            'requested_by' => 'required|integer',
            'authoriser_id' => 'required|integer',
        ]);

        try {
            $data = [
                'updated_by' => $validated['requested_by'],
                'authoriser_id' => $validated['authoriser_id'],
            ];

            foreach (['product_id', 'field_name', 'field_type', 'required', 'status'] as $key) {
                if ($request->has($key)) {
                    $data[$key] = $validated[$key] ?? null;
                }
            }

            $updatedField = $this->securityManagementService->update($field, $data);
            return response()->json([
                'message' => 'Security management field updated successfully.',
                'data' => $updatedField
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

  
    public function destroy(Request $request, $id)
    {
        $field = SecurityManagement::findOrFail($id);

        $request->validate([
            'requested_by' => 'nullable',
            'authoriser_id' => 'nullable',
        ]);

        try {
            $this->securityManagementService->delete($field);
            return response()->json([
                'message' => 'Security management field deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────
    // Pending Approval Endpoints
    // ──────────────────────────────────────────────

    /**
     * List all pending security management requests
     *
     * @OA\Get(
     *     path="/api/v1/admin/pending-security-management",
     *     summary="Get Pending Security Management Requests",
     *     tags={"Security Management Approvals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of pending requests")
     * )
     */
    public function pending(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $pending = $this->securityManagementService->getPendingRequests($perPage);
        return response()->json($pending);
    }

    /**
     * Get a single pending security management request
     *
     * @OA\Get(
     *     path="/api/v1/admin/pending-security-management/{pendingSecurityManagement}",
     *     summary="Get Single Pending Security Management Request",
     *     tags={"Security Management Approvals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="pendingSecurityManagement", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Pending request details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showPending($id)
    {
        $pending = PendingSecurityManagement::with(['requester', 'category', 'productType', 'mainRecord'])
            ->findOrFail($id);
        return response()->json($pending);
    }

    /**
     * Compare pending changes against the current record
     *
     * @OA\Get(
     *     path="/api/v1/admin/pending-security-management/{pendingSecurityManagement}/compare",
     *     summary="Compare Pending Security Management Changes",
     *     description="Retrieve changes between old and new data for update requests",
     *     tags={"Security Management Approvals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="pendingSecurityManagement", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Comparison of changes"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showPendingWithComparison($id)
    {
        $pending = PendingSecurityManagement::findOrFail($id);
        $changes = [];

        if (in_array($pending->request_type, ['update', 'delete']) && $pending->security_management_id) {
            $currentData = SecurityManagement::find($pending->security_management_id);

            if ($currentData && $pending->request_type === 'update') {
                $fieldsToCompare = ['product_id', 'field_name', 'field_type', 'required', 'status'];

                foreach ($fieldsToCompare as $field) {
                    if ($currentData->{$field} != $pending->{$field}) {
                        $changes[$field] = [
                            'old' => $currentData->{$field},
                            'new' => $pending->{$field},
                        ];
                    }
                }
            }
        }

        return response()->json([
            'pending' => $pending,
            'changes' => $changes,
        ]);
    }

    /**
     * Approve a pending security management request
     *
     * @OA\Post(
     *     path="/api/v1/admin/pending-security-management/{pendingSecurityManagement}/approve",
     *     summary="Approve Pending Security Management Request",
     *     tags={"Security Management Approvals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="pendingSecurityManagement", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Approved successfully"),
     *     @OA\Response(response=403, description="Unauthorized or already processed"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function approve($id)
    {
        $pending = PendingSecurityManagement::findOrFail($id);

        try {
            $result = $this->securityManagementService->approveRequest($pending);
            return response()->json([
                'message' => 'Request approved successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    /**
     * Reject a pending security management request
     *
     * @OA\Post(
     *     path="/api/v1/admin/pending-security-management/{pendingSecurityManagement}/reject",
     *     summary="Reject Pending Security Management Request",
     *     tags={"Security Management Approvals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="pendingSecurityManagement", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reason"},
     *             @OA\Property(property="reason", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Rejected successfully"),
     *     @OA\Response(response=403, description="Unauthorized or already processed"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function reject(Request $request, $id)
    {
        $validated = $request->validate(['reason' => 'required|string']);
        $pending = PendingSecurityManagement::findOrFail($id);

        try {
            $result = $this->securityManagementService->rejectRequest($pending, $validated['reason']);
            return response()->json([
                'message' => 'Request rejected successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }


    
    /**
     * Get security management fields by product ID
     *
     * @OA\Get(
     *     path="/api/v1/admin/security-management/category/{productId}",
     *     operationId="getSecurityManagementFieldsByCategory",
     *     summary="Get Security Management Fields by Product",
     *     description="Retrieve all active security management fields for a specific product type",
     *     tags={"Security Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         description="Product Type ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (1=Active, 0=Inactive)",
     *         required=false,
     *         @OA\Schema(type="integer", enum={0, 1})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="product_name", type="string", example="Bonds"),
     *             @OA\Property(property="product_code", type="string", example="BND"),
     *             @OA\Property(
     *                 property="fields",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="field_name", type="string", example="Issue Category"),
     *                     @OA\Property(property="field_type", type="string", example="Text"),
     *                     @OA\Property(property="required", type="boolean", example=true),
     *                     @OA\Property(property="status", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Product type not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getFieldsByCategory(Request $request, $productId)
    {
        $productType = \App\Models\ProductType::find($productId);

        if (!$productType) {
            return response()->json([
                'message' => 'Product type not found'
            ], 404);
        }

        $query = \App\Models\SecurityManagement::where('product_id', $productId);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to active fields only
            $query->where('status', 1);
        }

        $fields = $query->orderBy('id')->get([
            'id',
            'field_name',
            'field_type',
            'required',
            'status'
        ]);

        return response()->json([
            'product_id'   => $productType->id,
            'product_name' => $productType->name,
            'product_code' => $productType->code,
            'fields'       => $fields
        ]);
    }



    public function getFieldsByProductType(Request $request, $productId)
    {
        $productType = \App\Models\ProductType::find($productId);
        
        if (!$productType) {
            return response()->json([
                'message' => 'Product type not found'
            ], 404);
        }

        $query = \App\Models\SecurityManagement::where('product_id', $productId);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to active fields only
            $query->where('status', 1);
        }

        $fields = $query->orderBy('id')->get([
            'id',
            'field_name',
            'field_type',
            'required',
            'status'
        ]);

        return response()->json([
            'product_type_id' => $productType->id,
            'product_type_name' => $productType->name,
            'product_type_code' => $productType->code,
            'fields' => $fields
        ]);
    }

    /**
     * Get all security management fields grouped by category
     *
     * @OA\Get(
     *     path="/api/v1/admin/security-management/all",
     *     operationId="getAllSecurityManagementFields",
     *     summary="Get All Security Management Fields",
     *     description="Retrieve all security management fields grouped by market category",
     *     tags={"Security Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="category_id", type="integer"),
     *                 @OA\Property(property="category_name", type="string"),
     *                 @OA\Property(property="fields", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getAllFields()
    {
        $categories = \App\Models\MarketCategory::where('is_active', true)
            ->with(['securityManagementFields' => function($query) {
                $query->where('status', 1)->orderBy('id');
            }])
            ->get(['id', 'name', 'code']);

        $result = $categories->map(function($category) {
            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'category_code' => $category->code,
                'fields' => $category->securityManagementFields
            ];
        });

        return response()->json($result);
    }
}
