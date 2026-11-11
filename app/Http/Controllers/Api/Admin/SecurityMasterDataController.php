<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityMasterData;
use App\Models\PendingSecurityMasterData;
use App\Services\SecurityMasterDataService;
use App\Services\SecurityMasterDataActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SecurityMasterDataController extends Controller
{
    protected $securityMasterService;
    protected $externalUserService;
    protected $activityLogService;

    public function __construct(SecurityMasterDataService $securityMasterService, \App\Services\ExternalUserService $externalUserService, SecurityMasterDataActivityLogService $activityLogService)
    {
        $this->securityMasterService = $securityMasterService;
        $this->externalUserService = $externalUserService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Get all security master records
     */
    #[OA\Get(
        path: "/api/v1/admin/security-master",
        operationId: "getSecurityMasterRecords",
        summary: "Get All Security Master Records",
        description: "Retrieve all security master records",
        tags: ["Security Master Data"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "category_id", in: "query", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    
    {
         $categoryId = $request->get('category_id');

        $this->activityLogService->logView('List of security master data viewed', [
            'category_id' => $categoryId
        ]);

        $securities = $this->securityMasterService->getAllSecurities($categoryId);

        $users = $this->externalUserService->getAllUsers();

        $data = $securities->map(function ($security) use ($users) {
            $creator = $users->get($security->created_by);
            $creatorName = $creator ? trim(($creator['firstname'] ?? '') . ' ' . ($creator['lastname'] ?? '')) : null;

            return [
                'id' => $security->id,
                'created_at' => $security->created_at,
                'security_name' => $security->security_name,
                'category' => $security->category ? [
                    'id' => $security->category->id,
                    'name' => $security->category->name,
                ] : null,
                'product' => $security->productType ? [
                    'id' => $security->productType->id,
                    'name' => $security->productType->name,
                ] : null,
                'status' => $security->status,
                'created_by' => $security->created_by,
                'created_by_name' => $creatorName,
                'approval_status' => $security->approval_status,
                'lifecycle_status' => $security->lifecycle_status,
                'redemption_date' => $security->redemption_date?->format('Y-m-d'),
                'fields' => $security->fieldValues->map(function ($value) {
                    return [
                        'field_id' => $value->field_id,
                        'field_name' => $value->field->field_name ?? null,
                        'value' => $value->field_value,
                    ];
                }),
                'amortisation_schedules' => $security->amortisationSchedules,
                'call_schedules' => $security->callSchedules,
                'reset_schedules' => $security->resetSchedules,
            ];
        });

        return response()->json($data);
    }

    /**
     * Create a new security master record (Request)
     */
    #[OA\Post(
        path: "/api/v1/admin/security-master",
        operationId: "createSecurityMaster",
        summary: "Create Security Master Record (Request)",
        description: "Submit a request to create a new security",
        tags: ["Security Master Data"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["category_id", "product_id", "fields", "authoriser_id"],
                properties: [
                    new OA\Property(property: "category_id", type: "integer"),
                    new OA\Property(property: "product_id", type: "integer"),
                    new OA\Property(property: "fields", type: "array", items: new OA\Items(properties: [
                        new OA\Property(property: "field_id", type: "integer"),
                        new OA\Property(property: "field_value", type: "string")
                    ])),
                    new OA\Property(property: "status", type: "integer"),
                    new OA\Property(property: "created_by", type: "integer"),
                    new OA\Property(property: "authoriser_id", type: "integer")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Request submitted successfully"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $rules = [
            'category_id' => 'required|exists:market_categories,id',
            'product_id' => 'required',
            'fields' => 'required|array',
            'fields.*.field_id' => 'required|exists:security_management,id',
            'fields.*.field_value' => 'nullable',
            'status' => 'sometimes|boolean',
            'created_by' => 'required|integer',
            'authoriser_id' => 'required|integer',
        ];

        // Check if Amortisation Schedule is required
        $optionTypeFieldId = \App\Models\SecurityManagement::where('field_name', 'Option Type')->value('id');
        $requiresSchedule = false;

        if ($optionTypeFieldId && is_array($request->fields)) {
            foreach ($request->fields as $field) {
                if (isset($field['field_id']) && $field['field_id'] == $optionTypeFieldId) {
                    $value = strtolower($field['field_value'] ?? '');
                    if (in_array($value, ['amortised', 'moratorium'])) {
                        $requiresSchedule = true;
                        break;
                    }
                }
            }
        }

        if ($requiresSchedule) {
            $rules['amortisation_schedule'] = 'required|file|mimes:csv,txt';
        }

        // Check if Call Schedule is required (Callable option type)
        if ($this->requiresCallSchedule($request->fields, $optionTypeFieldId)) {
            $rules['call_schedule'] = 'required|file|mimes:csv,txt';
        }

        // Check if Reset Schedule is required (Perpetual option type)
        if ($this->requiresResetSchedule($request->fields, $optionTypeFieldId)) {
            $rules['reset_schedule'] = 'required|file|mimes:csv,txt';
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('amortisation_schedule')) {
            $validated['amortisation_schedule_data'] = $this->parseAmortisationScheduleCsv($request->file('amortisation_schedule'));
        }

        if ($request->hasFile('call_schedule')) {
            $validated['call_schedule_data'] = $this->parseCallScheduleCsv($request->file('call_schedule'));
            $validated['fields'] = $this->syncCallDateField($validated['fields'], $validated['call_schedule_data']);
        }

        if ($request->hasFile('reset_schedule')) {
            $validated['reset_schedule_data'] = $this->parseResetScheduleCsv($request->file('reset_schedule'));
        }

        try {
            $pending = $this->securityMasterService->createRequest($validated);
            $this->activityLogService->logSecurityMasterCreation($pending, $validated);

            return response()->json([
                'message' => 'Security master creation request submitted for approval.',
                'data' => $pending
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->activityLogService->logView('Security Master Data creation validation failed', [
                'user_id' => $validated['created_by'] ?? null,
                'error' => 'Validation failed'
            ]);
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            $this->activityLogService->logView('Security Master Data creation failed', [
                'user_id' => $validated['created_by'] ?? null,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get a single security master record
     */
    #[OA\Get(
        path: "/api/v1/admin/security-master/{id}",
        operationId: "getSecurityMaster",
        summary: "Get Security Master Record",
        tags: ["Security Master Data"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function show($id): JsonResponse
    {
        try {
            $this->activityLogService->logView('Security Master Data details viewed', [
                'security_id' => $id
            ]);
            $security = $this->securityMasterService->getSecurity($id);
            return response()->json($security);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Security not found'], 404);
        }
    }

    /**
     * Update a security master record (Request)
     */
    #[OA\Put(
        path: "/api/v1/admin/security-master/{id}",
        operationId: "updateSecurityMaster",
        summary: "Update Security Master Record (Request)",
        description: "Submit a request to update a security",
        tags: ["Security Master Data"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: "security_name", type: "string"),
            new OA\Property(property: "product_id", type: "integer"),
            new OA\Property(property: "fields", type: "array", items: new OA\Items(properties: [
                new OA\Property(property: "field_id", type: "integer"),
                new OA\Property(property: "field_value", type: "string")
            ])),
            new OA\Property(property: "updated_by", type: "integer"),
            new OA\Property(property: "authoriser_id", type: "integer")
        ])),
        responses: [
            new OA\Response(response: 200, description: "Request submitted successfully"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function update(Request $request, $id): JsonResponse
    {
        $rules = [
            'security_name' => 'sometimes|string|max:255',
            'product_id' => 'sometimes|exists:product_types,id',
            'fields' => 'sometimes|array',
            'fields.*.field_id' => 'required|exists:security_management,id',
            'fields.*.field_value' => 'nullable',
            'status' => 'sometimes|boolean',
            'updated_by' => 'required|integer',
            'authoriser_id' => 'required|integer',
        ];

        // Check if Amortisation Schedule is required
        $optionTypeFieldId = \App\Models\SecurityManagement::where('field_name', 'Option Type')->value('id');
        $requiresSchedule = false;

        if ($optionTypeFieldId && is_array($request->fields)) {
            foreach ($request->fields as $field) {
                if (isset($field['field_id']) && $field['field_id'] == $optionTypeFieldId) {
                    $value = strtolower($field['field_value'] ?? '');
                    if (in_array($value, ['amortised', 'moratorium'])) {
                        $requiresSchedule = true;
                        break;
                    }
                }
            }
        }

        if ($requiresSchedule) {
            // For update, we might already have schedules, but if it changes to amortised, it needs to provide a new one if it wasn't amortised before. 
            // We enforce upload for safety on updates involving option type changes.
            $rules['amortisation_schedule'] = 'required|file|mimes:csv,txt';
        }

        // Check if Call Schedule is required (Callable option type)
        if ($this->requiresCallSchedule($request->fields, $optionTypeFieldId)) {
            $rules['call_schedule'] = 'required|file|mimes:csv,txt';
        }

        // Check if Reset Schedule is required (Perpetual option type)
        if ($this->requiresResetSchedule($request->fields, $optionTypeFieldId)) {
            $rules['reset_schedule'] = 'required|file|mimes:csv,txt';
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('amortisation_schedule')) {
            $validated['amortisation_schedule_data'] = $this->parseAmortisationScheduleCsv($request->file('amortisation_schedule'));
        }

        if ($request->hasFile('call_schedule')) {
            $validated['call_schedule_data'] = $this->parseCallScheduleCsv($request->file('call_schedule'));
            if (isset($validated['fields'])) {
                $validated['fields'] = $this->syncCallDateField($validated['fields'], $validated['call_schedule_data']);
            }
        }

        if ($request->hasFile('reset_schedule')) {
            $validated['reset_schedule_data'] = $this->parseResetScheduleCsv($request->file('reset_schedule'));
        }

        try {
            $security = SecurityMasterData::findOrFail($id);
            $pending = $this->securityMasterService->updateRequest($security, $validated);
            $this->activityLogService->logSecurityMasterUpdate($security, $validated);

            return response()->json([
                'message' => 'Security master update request submitted for approval.',
                'data' => $pending
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->activityLogService->logView('Security Master Data update validation failed', [
                'user_id' => $validated['updated_by'] ?? null,
                'security_id' => $id,
                'error' => 'Validation failed'
            ]);
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            $this->activityLogService->logView('Security Master Data update failed', [
                'user_id' => $validated['updated_by'] ?? null,
                'security_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete a security master record (Request)
     */
    #[OA\Delete(
        path: "/api/v1/admin/security-master/{id}",
        operationId: "deleteSecurityMaster",
        summary: "Delete Security Master Record (Request)",
        tags: ["Security Master Data"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [
            new OA\Response(response: 200, description: "Request submitted successfully"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function destroy(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'updated_by' => 'required|integer',
            'authoriser_id' => 'required|integer',
        ]);

        try {
            $security = SecurityMasterData::findOrFail($id);
            $pending = $this->securityMasterService->deleteRequest($security, $validated);
            $this->activityLogService->logSecurityMasterDeletion($security, $validated);

            return response()->json([
                'message' => 'Security master deletion request submitted for approval.',
                'data' => $pending
            ]);
        } catch (\Exception $e) {
            $this->activityLogService->logView('Security Master Data deletion failed', [
                'user_id' => $validated['updated_by'] ?? null,
                'security_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Security not found'], 404);
        }
    }

    /**
     * Record that the issuer has called the security on a scheduled call date (Request)
     */
    #[OA\Post(
        path: "/api/v1/admin/security-master/{id}/call-schedules/{callId}/exercise",
        operationId: "exerciseSecurityMasterCall",
        summary: "Exercise Call (Request)",
        description: "Submit a request to record that the issuer called the security. A call with a call_amount is a partial redemption: on approval Outstanding Value is reduced by that amount and the security stays active. A call without an amount, or one that brings Outstanding Value to 0, redeems the security: it is marked as called and deactivated, remaining call dates are marked not exercised and remaining reset dates are cancelled.",
        tags: ["Security Master Data"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "callId", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ["updated_by", "authoriser_id"],
            properties: [
                new OA\Property(property: "exercise_date", type: "string", format: "date", description: "Defaults to the scheduled call date"),
                new OA\Property(property: "call_price", type: "number", description: "Defaults to the scheduled call price"),
                new OA\Property(property: "call_amount", type: "number", description: "Principal retired, in Outstanding Value units (₦'bn). Defaults to the scheduled call amount; none = full call"),
                new OA\Property(property: "updated_by", type: "integer"),
                new OA\Property(property: "authoriser_id", type: "integer")
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: "Request submitted successfully"),
            new OA\Response(response: 404, description: "Security or call date not found"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function exerciseCall(Request $request, $id, $callId): JsonResponse
    {
        $validated = $request->validate([
            'exercise_date' => 'nullable|date',
            'call_price' => 'nullable|numeric',
            'call_amount' => 'nullable|numeric|gt:0',
            'updated_by' => 'required|integer',
            'authoriser_id' => 'required|integer',
        ]);

        if (!empty($validated['exercise_date'])) {
            $validated['exercise_date'] = \Carbon\Carbon::parse($validated['exercise_date'])->format('Y-m-d');
        }

        $security = SecurityMasterData::find($id);
        $call = $security ? $security->callSchedules()->find($callId) : null;

        if (!$call) {
            return response()->json(['message' => 'Security or call date not found'], 404);
        }

        try {
            $pending = $this->securityMasterService->callRequest($security, $call, $validated);
            $this->activityLogService->logView('Security Master Data call exercise requested', [
                'security_id' => $security->id,
                'call_schedule_id' => $call->id,
                'pending_id' => $pending->id,
            ]);

            return response()->json([
                'message' => 'Call exercise request submitted for approval.',
                'data' => $pending
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Apply the actual coupon rate on a scheduled reset date (Request)
     */
    #[OA\Post(
        path: "/api/v1/admin/security-master/{id}/reset-schedules/{resetId}/apply",
        operationId: "applySecurityMasterReset",
        summary: "Apply Coupon Reset (Request)",
        description: "Submit a request to apply the new coupon rate for a scheduled reset date. On approval the reset date is marked applied and the security's Coupon (%) is updated.",
        tags: ["Security Master Data"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "resetId", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ["new_coupon_rate", "updated_by", "authoriser_id"],
            properties: [
                new OA\Property(property: "new_coupon_rate", type: "number"),
                new OA\Property(property: "applied_date", type: "string", format: "date", description: "Defaults to the scheduled reset date"),
                new OA\Property(property: "benchmark", type: "string"),
                new OA\Property(property: "spread", type: "number"),
                new OA\Property(property: "updated_by", type: "integer"),
                new OA\Property(property: "authoriser_id", type: "integer")
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: "Request submitted successfully"),
            new OA\Response(response: 404, description: "Security or reset date not found"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function applyReset(Request $request, $id, $resetId): JsonResponse
    {
        $validated = $request->validate([
            'new_coupon_rate' => 'required|numeric',
            'applied_date' => 'nullable|date',
            'benchmark' => 'nullable|string|max:255',
            'spread' => 'nullable|numeric',
            'updated_by' => 'required|integer',
            'authoriser_id' => 'required|integer',
        ]);

        if (!empty($validated['applied_date'])) {
            $validated['applied_date'] = \Carbon\Carbon::parse($validated['applied_date'])->format('Y-m-d');
        }

        $security = SecurityMasterData::find($id);
        $reset = $security ? $security->resetSchedules()->find($resetId) : null;

        if (!$reset) {
            return response()->json(['message' => 'Security or reset date not found'], 404);
        }

        try {
            $pending = $this->securityMasterService->resetRequest($security, $reset, $validated);
            $this->activityLogService->logView('Security Master Data coupon reset requested', [
                'security_id' => $security->id,
                'reset_schedule_id' => $reset->id,
                'pending_id' => $pending->id,
            ]);

            return response()->json([
                'message' => 'Coupon reset request submitted for approval.',
                'data' => $pending
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // Pending Requests

    #[OA\Get(
        path: "/api/v1/admin/pending-security-master",
        operationId: "getPendingSecurityMasterRecords",
        summary: "Get Pending Security Master Requests",
        tags: ["Security Master Approvals"],
        security: [["bearerAuth" => []]],
        responses: [new OA\Response(response: 200, description: "Successful operation")]
    )]
    public function pending(Request $request): JsonResponse
    {
        $this->activityLogService->logView('Pending security master data viewed', []);

        $pending = $this->securityMasterService->getPendingRequests();

        $fieldIds = $pending->pluck('fields_data.*.field_id')->flatten()->unique();
        $fields = \App\Models\SecurityManagement::whereIn('id', $fieldIds)->pluck('field_name', 'id');

        $data = $pending->map(function ($item) use ($fields) {
            $record = [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'category_name' => $item->category->name ?? null,
                'product_id' => $item->product_id,
                'product_name' => $item->productType->name ?? null,
                'security_name' => $item->security_name,
                'status' => $item->status,
                'request_type' => $item->request_type,
                'approval_status' => $item->approval_status,
                'fields_data' => $item->fields_data,
                'created_at' => $item->created_at,
                'requested_by' => $item->requested_by,
                'requester_name' => $item->requester ? ($item->requester->first_name . ' ' . $item->requester->last_name) : null,
                'rejection_reason' => $item->rejection_reason,
                'amortisation_schedule' => $item->amortisation_schedule,
                'call_schedule' => $item->call_schedule,
                'reset_schedule' => $item->reset_schedule,
                'event_data' => $item->event_data,
            ];

            if (!empty($record['fields_data'])) {
                $record['fields_data'] = collect($record['fields_data'])->map(function ($field) use ($fields) {
                    $field['field_name'] = $fields[$field['field_id']] ?? null;
                    return $field;
                })->toArray();
            }

            return $record;
        });

        return response()->json($data);
    }

    #[OA\Get(
        path: "/api/v1/admin/pending-security-master/{pendingSecurity}",
        operationId: "getPendingSecurityMasterRecord",
        summary: "Get Single Pending Security Master Request",
        tags: ["Security Master Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "pendingSecurity", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Successful operation")]
    )]
    public function showPending(PendingSecurityMasterData $pendingSecurity): JsonResponse
    {
        $this->activityLogService->logView('Pending security master data details viewed', [
            'pending_id' => $pendingSecurity->id
        ]);
        return response()->json($pendingSecurity->load(['requester', 'category', 'mainRecord']));
    }

    #[OA\Get(
        path: "/api/v1/admin/pending-security-master/{pendingSecurity}/compare",
        operationId: "comparePendingSecurityMaster",
        summary: "Compare Pending Security Master Changes",
        tags: ["Security Master Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "pendingSecurity", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Successful operation")]
    )]
    public function showPendingWithComparison(PendingSecurityMasterData $pendingSecurity): JsonResponse
    {
        $this->activityLogService->logView('Pending security master data comparison viewed', [
            'pending_id' => $pendingSecurity->id,
            'request_type' => $pendingSecurity->request_type
        ]);
        $changes = [];
        $currentData = null;

        if (in_array($pendingSecurity->request_type, ['update', 'delete', 'call', 'reset']) && $pendingSecurity->security_master_id) {
            $currentData = SecurityMasterData::with(['fieldValues.field', 'amortisationSchedules', 'callSchedules', 'resetSchedules'])->find($pendingSecurity->security_master_id);

            if ($currentData && $pendingSecurity->request_type === 'update') {
                if ($currentData->security_name !== $pendingSecurity->security_name) {
                    $changes['security_name'] = ['old' => $currentData->security_name, 'new' => $pendingSecurity->security_name];
                }
                if ($currentData->status !== $pendingSecurity->status) {
                    $changes['status'] = ['old' => $currentData->status, 'new' => $pendingSecurity->status];
                }
            }
        }

        return response()->json([
            'action_type' => $pendingSecurity->request_type,
            'pending_request' => $pendingSecurity,
            'old_data' => $currentData,
            'new_data' => [
                'security_name' => $pendingSecurity->security_name,
                'status' => $pendingSecurity->status,
                'fields_data' => $pendingSecurity->fields_data,
                'amortisation_schedule' => $pendingSecurity->amortisation_schedule,
                'call_schedule' => $pendingSecurity->call_schedule,
                'reset_schedule' => $pendingSecurity->reset_schedule,
                'event_data' => $pendingSecurity->event_data
            ],
            'changes' => $changes
        ]);
    }

    #[OA\Post(
        path: "/api/v1/admin/pending-security-master/{pendingSecurity}/approve",
        operationId: "approveSecurityMaster",
        summary: "Approve Pending Security Master Request",
        tags: ["Security Master Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "pendingSecurity", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Approved successfully")]
    )]
    public function approve(PendingSecurityMasterData $pendingSecurity): JsonResponse
    {
        try {
            $result = $this->securityMasterService->approveRequest($pendingSecurity);
            $this->activityLogService->logApproval($pendingSecurity, $pendingSecurity->authoriser_id);
            return response()->json([
                'message' => 'Request approved successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->activityLogService->logView('Security Master Data approval failed', [
                'pending_id' => $pendingSecurity->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    #[OA\Post(
        path: "/api/v1/admin/pending-security-master/{pendingSecurity}/reject",
        operationId: "rejectSecurityMaster",
        summary: "Reject Pending Security Master Request",
        tags: ["Security Master Approvals"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(required: ["reason"], properties: [new OA\Property(property: "reason", type: "string")])),
        responses: [new OA\Response(response: 200, description: "Rejected successfully")]
    )]
    public function reject(Request $request, PendingSecurityMasterData $pendingSecurity): JsonResponse
    {
        $validated = $request->validate(['reason' => 'required|string']);

        try {
            $result = $this->securityMasterService->rejectRequest($pendingSecurity, $validated['reason']);
            $this->activityLogService->logRejection($pendingSecurity, $pendingSecurity->authoriser_id, $validated['reason']);
            return response()->json([
                'message' => 'Request rejected successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->activityLogService->logView('Security Master Data rejection failed', [
                'pending_id' => $pendingSecurity->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    #[OA\Post(
        path: "/api/v1/admin/security-master/bulk-upload",
        operationId: "bulkUploadSecurityMaster",
        summary: "Bulk Upload Security Master Records",
        description: "Upload an Excel/CSV file to create multiple security records",
        tags: ["Security Master Data"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["file", "category_id", "created_by", "authoriser_id"],
                    properties: [
                        new OA\Property(property: "file", type: "string", format: "binary"),
                        new OA\Property(property: "category_id", type: "integer"),
                        new OA\Property(property: "created_by", type: "integer"),
                        new OA\Property(property: "authoriser_id", type: "integer"),
                        new OA\Property(property: "product_id", type: "integer", description: "Product type whose active fields the columns are matched against (by field id or by field name)."),
                        new OA\Property(property: "ignore_unknown_columns", type: "boolean", description: "By default a column that matches no active field rejects the whole file (422, with unknown_columns and expected_columns). Set true to skip such columns; they are returned in `warnings`."),
                        new OA\Property(property: "amortisation_schedules", type: "object", description: "Per-row Amortisation Schedule CSVs: amortisation_schedules[<row number>]=<file>. Row 1 is the header, so the first data row is 2."),
                        new OA\Property(property: "call_schedules", type: "object", description: "Per-row Call Schedule CSVs: call_schedules[<row number>]=<file>"),
                        new OA\Property(property: "reset_schedules", type: "object", description: "Per-row Reset Schedule CSVs: reset_schedules[<row number>]=<file>")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Upload processed successfully. `warnings` lists any columns skipped under ignore_unknown_columns."),
            new OA\Response(response: 422, description: "Validation Error. For an unrecognised column: errors.file (message), errors.unknown_columns, errors.expected_columns.")
        ]
    )]
    public function bulkUpload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'category_id' => 'required|exists:market_categories,id',
            'created_by' => 'required|integer',
            'authoriser_id' => 'required|integer',
            // By default a column that matches no active field fails the whole upload (422).
            // Set to true to skip such columns and get them back as `warnings` instead.
            'ignore_unknown_columns' => 'sometimes|boolean',
            // Per-row schedule CSVs, keyed by spreadsheet row number (header is row 1)
            'amortisation_schedules' => 'sometimes|array',
            'amortisation_schedules.*' => 'file|mimes:csv,txt',
            'call_schedules' => 'sometimes|array',
            'call_schedules.*' => 'file|mimes:csv,txt',
            'reset_schedules' => 'sometimes|array',
            'reset_schedules.*' => 'file|mimes:csv,txt',
        ]);

        $amortisationSchedules = $this->parseBulkScheduleFiles($request, 'amortisation_schedules', 'parseAmortisationScheduleCsv');
        $callSchedules = $this->parseBulkScheduleFiles($request, 'call_schedules', 'parseCallScheduleCsv');
        $resetSchedules = $this->parseBulkScheduleFiles($request, 'reset_schedules', 'parseResetScheduleCsv');

        try {
            $this->activityLogService->logBulkUpload([
                'category_id' => $request->category_id,
                'product_id' => $request->product_id,
                'created_by' => $request->created_by,
                'authoriser_id' => $request->authoriser_id
            ]);

            $import = new \App\Imports\SecurityMasterImport(
                $this->securityMasterService,
                $request->category_id,
                $request->product_id,
                $request->created_by,
                $request->authoriser_id,
                $amortisationSchedules,
                $callSchedules,
                $resetSchedules,
                $request->boolean('ignore_unknown_columns')
            );

            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            return response()->json([
                'message' => 'Bulk upload processed successfully. Valid records have been submitted for approval.',
                'warnings' => $import->getWarnings(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->activityLogService->logView('Security Master Data bulk upload validation failed', [
                'user_id' => $request->created_by,
                'error' => 'Validation failed'
            ]);
            return response()->json([
                'message' => 'Validation failed for some records in the file.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            $this->activityLogService->logView('Security Master Data bulk upload failed', [
                'user_id' => $request->created_by,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper to parse Amortisation Schedule CSV
     */
    protected function parseAmortisationScheduleCsv(\Illuminate\Http\UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));
        
        if (empty($data)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'amortisation_schedule' => ['The uploaded CSV file is empty.']
            ]);
        }

        $headers = array_map('trim', array_shift($data));
        
        // Define required headers
        $requiredHeaders = [
            'Date',
            'Scheduled Coupon Cashflow',
            'Scheduled Principal Cashflow',
            'Outstanding Amount'
        ];

        foreach ($requiredHeaders as $requiredHeader) {
            if (!in_array($requiredHeader, $headers)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amortisation_schedule' => ['The CSV must contain the column: ' . $requiredHeader]
                ]);
            }
        }

        $parsedData = [];
        foreach ($data as $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            $rowData = array_combine($headers, $row);
            
            // Format check for Date
            $dateVal = $rowData['Date'] ?? null;
            if ($dateVal) {
                try {
                    $dateVal = \Carbon\Carbon::parse($dateVal)->format('Y-m-d');
                } catch (\Exception $e) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'amortisation_schedule' => ['Invalid Date format in CSV row.']
                    ]);
                }
            }

            $parsedData[] = [
                'date' => $dateVal,
                'scheduled_coupon_cashflow' => is_numeric($rowData['Scheduled Coupon Cashflow']) ? (float)$rowData['Scheduled Coupon Cashflow'] : 0,
                'scheduled_principal_cashflow' => is_numeric($rowData['Scheduled Principal Cashflow']) ? (float)$rowData['Scheduled Principal Cashflow'] : 0,
                'outstanding_amount' => is_numeric($rowData['Outstanding Amount']) ? (float)$rowData['Outstanding Amount'] : null,
            ];
        }

        if (empty($parsedData)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'amortisation_schedule' => ['The uploaded CSV file contains no valid data rows.']
            ]);
        }

        return $parsedData;
    }

    /**
     * Determine whether the submitted Option Type is Callable (requires a Call Schedule upload).
     */
    protected function requiresCallSchedule($fields, $optionTypeFieldId): bool
    {
        return $this->securityMasterService->requiredSchedules($fields)['call'];
    }

    /**
     * Determine whether the submitted Option Type is Perpetual (requires a Reset Schedule upload).
     */
    protected function requiresResetSchedule($fields, $optionTypeFieldId): bool
    {
        return $this->securityMasterService->requiredSchedules($fields)['reset'];
    }

    /**
     * Parse per-row schedule CSVs sent with a bulk upload, e.g. call_schedules[5].
     * Keys are spreadsheet row numbers (row 1 is the header row).
     *
     * @return array [rowNumber => parsedScheduleData]
     */
    protected function parseBulkScheduleFiles(Request $request, string $input, string $parser): array
    {
        $parsed = [];

        foreach ((array)$request->file($input, []) as $rowNumber => $file) {
            try {
                $parsed[(int)$rowNumber] = $this->{$parser}($file);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $messages = collect($e->errors())->flatten()->map(fn($m) => "Row {$rowNumber}: {$m}")->all();
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "{$input}.{$rowNumber}" => $messages
                ]);
            }
        }

        return $parsed;
    }

    /**
     * Set the 'Call Date' field value (if submitted) to the earliest date in the call schedule
     */
    protected function syncCallDateField(array $fields, array $callSchedule): array
    {
        $callDateFieldId = \App\Models\SecurityManagement::where('field_name', 'Call Date')->pluck('id')->all();
        $firstCallDate = collect($callSchedule)->pluck('call_date')->filter()->sort()->first();

        if (empty($callDateFieldId) || !$firstCallDate) {
            return $fields;
        }

        return array_map(function ($field) use ($callDateFieldId, $firstCallDate) {
            if (isset($field['field_id']) && in_array($field['field_id'], $callDateFieldId)) {
                $field['field_value'] = $firstCallDate;
            }
            return $field;
        }, $fields);
    }

    /**
     * Helper to parse Call Schedule CSV
     */
    protected function parseCallScheduleCsv(\Illuminate\Http\UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));

        if (empty($data)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'call_schedule' => ['The uploaded CSV file is empty.']
            ]);
        }

        // Strip UTF-8 BOM from the first header, if present
        $headers = array_map(fn($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', $h)), array_shift($data));

        if (!in_array('Call Date', $headers)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'call_schedule' => ['The CSV must contain the column: Call Date']
            ]);
        }

        $parsedData = [];
        foreach ($data as $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $rowData = array_combine($headers, $this->normaliseCsvRow($row, $headers, 'call_schedule'));

            $dateVal = trim($rowData['Call Date'] ?? '');
            if ($dateVal === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'call_schedule' => ['Call Date is required on every CSV row.']
                ]);
            }

            try {
                $dateVal = \Carbon\Carbon::parse($dateVal)->format('Y-m-d');
            } catch (\Exception $e) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'call_schedule' => ['Invalid Call Date format in CSV row.']
                ]);
            }

            $priceVal = trim($rowData['Call Price'] ?? '');
            $amountVal = trim($rowData['Call Amount'] ?? '');

            // Call Amount: principal retired (₦'bn). Blank = full call.
            if ($amountVal !== '' && (!is_numeric($amountVal) || (float)$amountVal <= 0)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'call_schedule' => ["Call Amount must be a positive number (row with Call Date {$dateVal})."]
                ]);
            }

            $parsedData[] = [
                'call_date' => $dateVal,
                'call_price' => is_numeric($priceVal) ? (float)$priceVal : null,
                'call_amount' => $amountVal !== '' ? (float)$amountVal : null,
            ];
        }

        if (empty($parsedData)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'call_schedule' => ['The uploaded CSV file contains no valid data rows.']
            ]);
        }

        usort($parsedData, fn($a, $b) => strcmp($a['call_date'], $b['call_date']));

        return $parsedData;
    }

    /**
     * Align a CSV row with its headers: pad rows whose trailing optional cells were omitted,
     * and drop extra trailing cells as long as they are empty.
     */
    protected function normaliseCsvRow(array $row, array $headers, string $errorKey): array
    {
        $headerCount = count($headers);

        if (count($row) > $headerCount) {
            $extra = array_slice($row, $headerCount);
            if (!empty(array_filter($extra, fn($v) => trim((string)$v) !== ''))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $errorKey => ['A CSV row has more values than there are header columns.']
                ]);
            }
            $row = array_slice($row, 0, $headerCount);
        }

        return array_pad($row, $headerCount, '');
    }

    /**
     * Helper to parse coupon Reset Schedule CSV (Perpetual bonds)
     * Columns: Reset Date (required), New Coupon Rate, Benchmark, Spread (optional)
     */
    protected function parseResetScheduleCsv(\Illuminate\Http\UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));

        if (empty($data)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'reset_schedule' => ['The uploaded CSV file is empty.']
            ]);
        }

        // Strip UTF-8 BOM from the first header, if present
        $headers = array_map(fn($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', $h)), array_shift($data));

        if (!in_array('Reset Date', $headers)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'reset_schedule' => ['The CSV must contain the column: Reset Date']
            ]);
        }

        $parsedData = [];
        foreach ($data as $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $rowData = array_map('trim', array_combine($headers, $this->normaliseCsvRow($row, $headers, 'reset_schedule')));

            $dateVal = $rowData['Reset Date'] ?? '';
            if ($dateVal === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'reset_schedule' => ['Reset Date is required on every CSV row.']
                ]);
            }

            try {
                $dateVal = \Carbon\Carbon::parse($dateVal)->format('Y-m-d');
            } catch (\Exception $e) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'reset_schedule' => ['Invalid Reset Date format in CSV row.']
                ]);
            }

            $rate = $rowData['New Coupon Rate'] ?? '';
            $spread = $rowData['Spread'] ?? '';
            $benchmark = $rowData['Benchmark'] ?? '';

            $parsedData[] = [
                'reset_date' => $dateVal,
                'new_coupon_rate' => is_numeric($rate) ? (float)$rate : null,
                'benchmark' => $benchmark !== '' ? $benchmark : null,
                'spread' => is_numeric($spread) ? (float)$spread : null,
            ];
        }

        if (empty($parsedData)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'reset_schedule' => ['The uploaded CSV file contains no valid data rows.']
            ]);
        }

        usort($parsedData, fn($a, $b) => strcmp($a['reset_date'], $b['reset_date']));

        return $parsedData;
    }
}