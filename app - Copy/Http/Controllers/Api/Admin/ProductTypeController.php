<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductType;
use App\Models\PendingProductType;
use App\Services\ProductTypeService;
use App\Services\ProductTypeActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductTypeController extends Controller
{
    protected $productTypeService;
    protected $activityLogService;

    public function __construct(ProductTypeService $productTypeService, ProductTypeActivityLogService $activityLogService)
    {
        $this->productTypeService = $productTypeService;
        $this->activityLogService = $activityLogService;
    }

    #[OA\Get(
        path: "/api/v1/admin/product-types",
        operationId: "getProductTypes",
        summary: "Get List of Product Types",
        tags: ["Product Types"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Successful operation",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(type: "object")
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index()
    {
        
        $this->activityLogService->logView('List of product types viewed');
        $productTypes = $this->productTypeService->getAllProductTypes();
        return response()->json($productTypes);
    }

    #[OA\Post(
        path: "/api/v1/admin/product-types",
        operationId: "createProductType",
        summary: "Create Product Type (Request)",
        description: "Submit a request to create a new product type, selecting an authoriser.",
        tags: ["Product Types"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["market_category_id", "name", "code", "authoriser_id"],
                properties: [
                    new OA\Property(property: "market_category_id", type: "integer"),
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "code", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "is_active", type: "boolean"),
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
                        new OA\Property(property: "message", type: "string", example: "Product Type creation request submitted for approval."),
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
            'market_category_id' => 'required|exists:market_categories,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'description' => 'nullable|string',
            'requested_by' => 'required',
            'authoriser_id' => 'required',
        ]);


        $existing = ProductType::where('name', $request->name)
            ->orWhere('code', $request->code)
            ->first();

        if ($existing) {
             $this->activityLogService->logView('Duplicate product type found during creation', [
                'requested_by' => $validated['requested_by'] ?? null,
                'name' => $validated['name'] ?? null,
                'code' => $validated['code'] ?? null,
                'duplicate_id' => $existing->id,
                'error_code' => 'DUPLICATE_ENTRY'
            ]);
            
             return response()->json([
                'message' => 'Product Type with this name or code already exists.',
                'error_code' => 'DUPLICATE_ENTRY'
            ], 400);
        }


        try {
            $pending = $this->productTypeService->createRequest($validated);
            $this->activityLogService->logProductTypeCreation($pending, $validated);
            return response()->json([
                'message' => 'Product Type creation request submitted for approval.',
                'data' => $pending
            ], 201);
        } catch (\Exception $e) {
            $this->activityLogService->logView('Product Type creation failed', [
                'user_id' => $validated['requested_by'] ?? null,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/admin/product-types/{productType}",
        operationId: "getProductType",
        summary: "Get Product Type Details",
        tags: ["Product Types"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "productType",
                in: "path",
                description: "Product Type ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function show(ProductType $productType): JsonResponse
    {
        $this->activityLogService->logView('Product Type details viewed', [
            'product_type_id' => $productType->id
        ]);
        return response()->json($productType->load('marketCategory'));
    }

    #[OA\Put(
        path: "/api/v1/admin/product-types/{productType}",
        operationId: "updateProductType",
        summary: "Update Product Type (Request)",
        description: "Submit a request to update an existing product type, selecting an authoriser.",
        tags: ["Product Types"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "productType",
                in: "path",
                description: "Product Type ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["authoriser_id"],
                properties: [
                    new OA\Property(property: "market_category_id", type: "integer"),
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "code", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "is_active", type: "boolean"),
                    new OA\Property(property: "authoriser_id", type: "integer", description: "The ID of the authoriser who should approve this request"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200, 
                description: "Request created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Product Type update request submitted for approval."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function update(Request $request, ProductType $productType): JsonResponse
    {
        $validated = $request->validate([
            'market_category_id' => 'sometimes|exists:market_categories,id',
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'requested_by' => 'required',
            'authoriser_id' => 'required',
        ]);

        try {
            $pending = $this->productTypeService->updateRequest($productType, $validated);
            $this->activityLogService->logProductTypeUpdate($productType, $validated);
            return response()->json([
                'message' => 'Product Type update request submitted for approval.',
                'data' => $pending
            ], 200);
        } catch (\Exception $e) {
            $this->activityLogService->logView('Product Type update failed', [
                'user_id' => $validated['requested_by'] ?? null,
                'product_type_id' => $productType->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    #[OA\Delete(
        path: "/api/v1/admin/product-types/{productType}",
        operationId: "deleteProductType",
        summary: "Delete Product Type (Request)",
        description: "Submit a request to delete a product type, selecting an authoriser. Pass authoriser_id in request body or query param.",
        tags: ["Product Types"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "productType",
                in: "path",
                description: "Product Type ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "authoriser_id",
                in: "query",
                description: "The ID of the authoriser who should approve this request",
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
                        new OA\Property(property: "message", type: "string", example: "Product Type deletion request submitted for approval."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function destroy(Request $request, ProductType $productType): JsonResponse
    {
        $validated = $request->validate([
            'authoriser_id' => 'required',
            'requested_by'  => 'required',
        ]);

        try {
            $pending = $this->productTypeService->deleteRequest($productType, $validated);
            $this->activityLogService->logProductTypeDeletion($productType, $validated);
            return response()->json([
                'message' => 'Product Type deletion request submitted for approval.',
                'data' => $pending
            ], 200);
        } catch (\Exception $e) {
            $this->activityLogService->logView('Product Type deletion failed', [
                'user_id' => $validated['requested_by'] ?? null,
                'product_type_id' => $productType->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // Pending Requests

    #[OA\Get(
        path: "/api/v1/admin/pending-product-types",
        operationId: "getPendingProductTypes",
        summary: "Get Pending Product Types",
        description: "Retrieve a list of pending product type requests.",
        tags: ["Product Type Approvals"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Successful operation",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(type: "object")
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function pending(Request $request): JsonResponse
    {
        $this->activityLogService->logView('Pending product types viewed');
        $pending = $this->productTypeService->getPendingRequests();
        return response()->json($pending);
    }

    #[OA\Get(
        path: "/api/v1/admin/pending-product-types/{pendingProductType}",
        operationId: "getPendingProductType",
        summary: "Get Single Pending Product Type",
        description: "Retrieve details of a single pending product type request",
        tags: ["Product Type Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "pendingProductType",
                in: "path",
                description: "Pending Product Type ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer"),
                        new OA\Property(property: "market_category_id", type: "integer"),
                        new OA\Property(property: "name", type: "string"),
                        new OA\Property(property: "code", type: "string"),
                        new OA\Property(property: "description", type: "string"),
                        new OA\Property(property: "is_active", type: "boolean"),
                        new OA\Property(property: "action_type", type: "string"),
                        new OA\Property(property: "requested_by", type: "integer"),
                        new OA\Property(property: "authoriser_id", type: "integer"),
                        new OA\Property(property: "approval_status", type: "string"),
                        new OA\Property(property: "created_at", type: "string"),
                        new OA\Property(property: "updated_at", type: "string")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Not found"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function showPending(PendingProductType $pendingProductType): JsonResponse
    {
        $this->activityLogService->logView('Pending product type details viewed', [
            'pending_id' => $pendingProductType->id
        ]);
        return response()->json($pendingProductType);
    }

    #[OA\Get(
        path: "/api/v1/admin/pending-product-types/{pendingProductType}/compare",
        operationId: "comparePendingProductType",
        summary: "Compare Pending Product Type Changes",
        description: "Retrieve changes between old and new data for update requests",
        tags: ["Product Type Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "pendingProductType",
                in: "path",
                description: "Pending Product Type ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Successful operation",
                content: new OA\JsonContent(
                    type: "object",
                    description: "Object containing only the fields that changed"
                )
            ),
            new OA\Response(response: 404, description: "Not found"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function showPendingWithComparison(PendingProductType $pendingProductType): JsonResponse
    {
        $this->activityLogService->logView('Pending product type comparison viewed', [
            'pending_id' => $pendingProductType->id,
            'request_type' => $pendingProductType->request_type
        ]);
        $changes = [];

        // For update and delete requests, fetch the current data
        if (in_array($pendingProductType->request_type, ['update', 'delete']) && $pendingProductType->product_type_id) {
            $currentData = ProductType::find($pendingProductType->product_type_id);
            
            if ($currentData) {
                // Calculate changes for update requests
                if ($pendingProductType->request_type === 'update') {
                    if ($currentData->market_category_id !== $pendingProductType->market_category_id) {
                        $changes['market_category_id'] = [
                            'old' => $currentData->market_category_id,
                            'new' => $pendingProductType->market_category_id
                        ];
                    }

                    if ($currentData->name !== $pendingProductType->name) {
                        $changes['name'] = [
                            'old' => $currentData->name,
                            'new' => $pendingProductType->name
                        ];
                    }
                    
                    if ($currentData->code !== $pendingProductType->code) {
                        $changes['code'] = [
                            'old' => $currentData->code,
                            'new' => $pendingProductType->code
                        ];
                    }
                    
                    if ($currentData->description !== $pendingProductType->description) {
                        $changes['description'] = [
                            'old' => $currentData->description,
                            'new' => $pendingProductType->description
                        ];
                    }
                    
                    if ($currentData->is_active !== $pendingProductType->is_active) {
                        $changes['is_active'] = [
                            'old' => $currentData->is_active,
                            'new' => $pendingProductType->is_active
                        ];
                    }
                }
            }
        }

        return response()->json($changes);
    }

    #[OA\Post(
        path: "/api/v1/admin/pending-product-types/{pendingProductType}/approve",
        operationId: "approveProductType",
        summary: "Approve Pending Product Type",
        description: "Approve a pending product type request. Only the assigned authoriser can approve.",
        tags: ["Product Type Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "pendingProductType",
                in: "path",
                description: "Pending Product Type ID",
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
    public function approve(PendingProductType $pendingProductType): JsonResponse
    {
        try {
            $result = $this->productTypeService->approveRequest($pendingProductType);
            $this->activityLogService->logApproval($pendingProductType, $pendingProductType->authoriser_id);
            return response()->json([
                'message' => 'Request approved successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->activityLogService->logView('Product Type approval failed', [
                'pending_id' => $pendingProductType->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    #[OA\Post(
        path: "/api/v1/admin/pending-product-types/{pendingProductType}/reject",
        operationId: "rejectProductType",
        summary: "Reject Pending Product Type",
        description: "Reject a pending product type request. Only the assigned authoriser can reject.",
        tags: ["Product Type Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "pendingProductType",
                in: "path",
                description: "Pending Product Type ID",
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
    public function reject(Request $request, PendingProductType $pendingProductType): JsonResponse
    {
        $validated = $request->validate(['reason' => 'required|string']);
        
        try {
            $result = $this->productTypeService->rejectRequest($pendingProductType, $validated['reason']);
            $this->activityLogService->logRejection($pendingProductType, $pendingProductType->authoriser_id, $validated['reason']);
            return response()->json([
                'message' => 'Request rejected successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->activityLogService->logView('Product Type rejection failed', [
                'pending_id' => $pendingProductType->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
