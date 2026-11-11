<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PendingUser;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    /**
     * The user service instance.
     *
     * @var UserService
     */
    protected $userService;

    /**
     * Create a new controller instance.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get paginated list of users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/v1/admin/users",
        operationId: "getUsers",
        summary: "Get List of Users",
        description: "Retrieve a paginated list of users",
        tags: ["User Management"],
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
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $users = $this->userService->getAllUsers($perPage);

        return response()->json($users, 200);
    }

    /**
     * Create a new user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/v1/admin/users",
        operationId: "createUser",
        summary: "Create User",
        description: "Create a new user (Admin only)",
        tags: ["User Management"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["firstname", "last_name", "email", "role", "password"],
                properties: [
                    new OA\Property(property: "firstname", type: "string"),
                    new OA\Property(property: "last_name", type: "string"),
                    new OA\Property(property: "email", type: "string", format: "email"),
                    new OA\Property(property: "department", type: "string"),
                    new OA\Property(property: "role", type: "string", description: "Role name (e.g., admin, user)"),
                    new OA\Property(property: "password", type: "string", format: "password"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "User created successfully"),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'department' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'exists:roles,name'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        try {
            // Check if user is super_admin to auto-approve
            $autoApprove = $request->user()->hasRole('super_admin');
            
            $user = $this->userService->createUser($validated, $autoApprove);

            return response()->json([
                'message' => $autoApprove 
                    ? 'User created and approved successfully.' 
                    : 'User created successfully. Pending approval.',
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create user.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get a single user.
     *
     * @param User $user
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/v1/admin/users/{user}",
        operationId: "getUser",
        summary: "Get User Details",
        description: "Retrieve details of a specific user",
        tags: ["User Management"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "user",
                in: "path",
                description: "User ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "User not found"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function show(User $user): JsonResponse
    {
        $user = $this->userService->getUserById($user);

        return response()->json($user, 200);
    }

    /**
     * Update a user.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    #[OA\Put(
        path: "/api/v1/admin/users/{user}",
        operationId: "updateUser",
        summary: "Update User",
        description: "Update details of a specific user",
        tags: ["User Management"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "user",
                in: "path",
                description: "User ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["firstname", "last_name", "email", "role"],
                properties: [
                    new OA\Property(property: "firstname", type: "string"),
                    new OA\Property(property: "last_name", type: "string"),
                    new OA\Property(property: "email", type: "string", format: "email"),
                    new OA\Property(property: "department", type: "string"),
                    new OA\Property(property: "role", type: "string"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "User updated successfully"),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 404, description: "User not found"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'department' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'exists:roles,name'],
        ]);

        try {
            $updatedUser = $this->userService->updateUser($user, $validated);

            return response()->json([
                'message' => 'User updated successfully.',
                'user' => $updatedUser,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update user.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete a user.
     *
     * @param User $user
     * @return JsonResponse
     */
    #[OA\Delete(
        path: "/api/v1/admin/users/{user}",
        operationId: "deleteUser",
        summary: "Delete User",
        description: "Soft delete a specific user",
        tags: ["User Management"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "user",
                in: "path",
                description: "User ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "User deleted successfully"),
            new OA\Response(response: 404, description: "User not found"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function destroy(User $user): JsonResponse
    {
        try {
            $this->userService->deleteUser($user, auth()->id());

            return response()->json([
                'message' => 'User deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Get paginated list of pending users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/v1/admin/pending-users",
        operationId: "getPendingUsers",
        summary: "Get Pending Users",
        description: "Retrieve a paginated list of pending users awaiting approval",
        tags: ["User Approvals"],
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
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function pending(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $users = $this->userService->getPendingUsers($perPage);

        return response()->json($users, 200);
    }

    /**
     * Approve a pending user.
     *
     * @param Request $request
     * @param PendingUser $pendingUser
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/v1/admin/pending-users/{pendingUser}/approve",
        operationId: "approveUser",
        summary: "Approve Pending User",
        description: "Approve a pending user registration",
        tags: ["User Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "pendingUser",
                in: "path",
                description: "Pending User ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "User approved successfully"),
            new OA\Response(response: 404, description: "Pending request not found"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function approve(Request $request, PendingUser $pendingUser): JsonResponse
    {
        try {
            $approvedUser = $this->userService->approveUser($pendingUser, auth()->id());

            return response()->json([
                'message' => 'User approved successfully.',
                'user' => $approvedUser,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject a pending user.
     *
     * @param Request $request
     * @param PendingUser $pendingUser
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/v1/admin/pending-users/{pendingUser}/reject",
        operationId: "rejectUser",
        summary: "Reject Pending User",
        description: "Reject a pending user registration",
        tags: ["User Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "pendingUser",
                in: "path",
                description: "Pending User ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["reason"],
                properties: [
                    new OA\Property(property: "reason", type: "string", example: "Incomplete information provided.")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "User rejected successfully"),
            new OA\Response(response: 404, description: "Pending request not found"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function reject(Request $request, PendingUser $pendingUser): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $rejectedUser = $this->userService->rejectUser(
                $pendingUser, 
                auth()->id(), 
                $validated['reason']
            );

            return response()->json([
                'message' => 'User rejected successfully.',
                'user' => $rejectedUser,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
