<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityType;
use App\Models\PendingSecurityType;
use App\Services\SecurityTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SecurityTypeController extends Controller
{
    protected $securityTypeService;

    public function __construct(SecurityTypeService $securityTypeService)
    {
        $this->securityTypeService = $securityTypeService;
    }

    #[OA\Get(
        path: "/api/v1/admin/security-types",
        operationId: "getSecurityTypes",
        summary: "Get List of Security Types",
        tags: ["Security Types"],
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
        $securityTypes = $this->securityTypeService->getAllSecurityTypes();
        return response()->json($securityTypes);
    }

    #[OA\Post(
        path: "/api/v1/admin/security-types",
        operationId: "createSecurityType",
        summary: "Create Security Type (Request)",
        description: "Submit a request to create a new security type, selecting an authoriser.",
        tags: ["Security Types"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "code", "requested_by", "authoriser_id"],
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "code", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "is_active", type: "boolean"),
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
                        new OA\Property(property: "message", type: "string", example: "Security Type creation request submitted for approval."),
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
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'requested_by' => 'required',
            'authoriser_id' => 'required',
        ]);

        try {
            $pending = $this->securityTypeService->createRequest($validated);
            return response()->json([
                'message' => 'Security Type creation request submitted for approval.',
                'data' => $pending
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/admin/security-types/import",
        operationId: "importSecurityType",
        summary: "Bulk Import Security Types",
        description: "Upload a CSV/Excel file to create multiple security type requests.",
        tags: ["Security Types"],
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
            'requested_by' => 'required',
            'authoriser_id' => 'required',
        ]);

        try {
            $import = new \App\Imports\SecurityTypesImport(
                $this->securityTypeService, 
                $request->requested_by, 
                $request->authoriser_id
            );
            
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            return response()->json([
                'message' => 'Bulk import processed. Requests have been submitted for approval.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/admin/security-types/{securityType}",
        operationId: "getSecurityType",
        summary: "Get Security Type Details",
        tags: ["Security Types"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "securityType",
                in: "path",
                description: "Security Type ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function show(SecurityType $securityType): JsonResponse
    {
        return response()->json($securityType);
    }

    #[OA\Put(
        path: "/api/v1/admin/security-types/{securityType}",
        operationId: "updateSecurityType",
        summary: "Update Security Type (Request)",
        description: "Submit a request to update an existing security type, selecting an authoriser.",
        tags: ["Security Types"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "securityType",
                in: "path",
                description: "Security Type ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["requested_by", "authoriser_id"],
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "code", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "is_active", type: "boolean"),
                    new OA\Property(property: "requested_by", type: "integer", description: "The ID of the user requesting this action (Inputter)"),
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
                        new OA\Property(property: "message", type: "string", example: "Security Type update request submitted for approval."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function update(Request $request, SecurityType $securityType): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'requested_by' => 'required',
            'authoriser_id' => 'required',
        ]);

        try {
            $pending = $this->securityTypeService->updateRequest($securityType, $validated);
            return response()->json([
                'message' => 'Security Type update request submitted for approval.',
                'data' => $pending
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    #[OA\Delete(
        path: "/api/v1/admin/security-types/{securityType}",
        operationId: "deleteSecurityType",
        summary: "Delete Security Type (Request)",
        description: "Submit a request to delete a security type, selecting an authoriser.",
        tags: ["Security Types"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "securityType",
                in: "path",
                description: "Security Type ID",
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
                        new OA\Property(property: "message", type: "string", example: "Security Type deletion request submitted for approval."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function destroy(Request $request, SecurityType $securityType): JsonResponse
    {
        $validated = $request->validate([
            'authoriser_id' => 'required',
            'requested_by'  => 'required',
        ]);

        try {
            $pending = $this->securityTypeService->deleteRequest($securityType, $validated);
            return response()->json([
                'message' => 'Security Type deletion request submitted for approval.',
                'data' => $pending
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // Pending Requests

    #[OA\Get(
        path: "/api/v1/admin/pending-security-types",
        operationId: "getPendingSecurityTypes",
        summary: "Get Pending Security Types",
        description: "Retrieve a paginated list of pending security type requests.",
        tags: ["Security Type Approvals"],
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
       
        $pending = $this->securityTypeService->getPendingRequests();
        return response()->json($pending);
    }

    #[OA\Post(
        path: "/api/v1/admin/pending-security-types/{pendingSecurityType}/approve",
        operationId: "approveSecurityType",
        summary: "Approve Pending Security Type",
        description: "Approve a pending security type request.",
        tags: ["Security Type Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "pendingSecurityType",
                in: "path",
                description: "Pending Security Type ID",
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
    public function approve(PendingSecurityType $pendingSecurityType): JsonResponse
    {
        try {
            $result = $this->securityTypeService->approveRequest($pendingSecurityType);
            return response()->json([
                'message' => 'Request approved successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    #[OA\Post(
        path: "/api/v1/admin/pending-security-types/{pendingSecurityType}/reject",
        operationId: "rejectSecurityType",
        summary: "Reject Pending Security Type",
        description: "Reject a pending security type request.",
        tags: ["Security Type Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "pendingSecurityType",
                in: "path",
                description: "Pending Security Type ID",
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
    public function reject(Request $request, PendingSecurityType $pendingSecurityType): JsonResponse
    {
        $validated = $request->validate(['reason' => 'required|string']);
        
        try {
            $result = $this->securityTypeService->rejectRequest($pendingSecurityType, $validated['reason']);
            return response()->json([
                'message' => 'Request rejected successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
