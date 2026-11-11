<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Models\User;
use App\Models\LoginLog;
use App\Models\LoginCount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Authentication", description: "Authentication & Security APIs")]
class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    // ========================= LOGIN =========================

    #[OA\Post(
        path: "/api/v1/login",
        operationId: "login",
        summary: "User Login",
        description: "Authenticate user and initiate OTP process",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "aboajahemmanue.l@gmail.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "@Password2")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OTP Required",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "otp_required", type: "boolean", example: true),
                        new OA\Property(property: "email", type: "string", example: "aboajahemmanue.l@gmail.com"),
                        new OA\Property(property: "message", type: "string", example: "OTP sent to your email.")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Invalid credentials"),
            new OA\Response(response: 403, description: "Account inactive or password expired"),
            new OA\Response(response: 429, description: "Account locked")
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $ipAddress = $request->ip();
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $this->logLoginAttempt(null, 'failed', $ipAddress, 'Invalid email or password', $request->email);
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (!$user->is_active) {
            $this->logLoginAttempt($user->id, 'failed', $ipAddress, 'Account not active', $user->email);
            return response()->json(['message' => 'Account is not active.'], 403);
        }

        if ($user->lockout_time) {
            $this->logLoginAttempt($user->id, 'failed', $ipAddress, 'Account locked', $user->email);
            return response()->json(['message' => 'Account locked. Reset password.'], 403);
        }

        $result = $this->authService->login($credentials);

        if (!$result) {
            $user->increment('failed_logins');

            $limit = optional(LoginCount::latest()->first())->login_count ?? 3;

            if ($user->failed_logins >= $limit) {
                $user->update(['lockout_time' => now()]);
                return response()->json(['message' => 'Account locked.'], 429);
            }

            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        // Password expiry check
        $ageLimit = optional(LoginCount::latest()->first())->password_age ?? 30;

        if ($user->password_changed_at && now()->diffInDays($user->password_changed_at) >= $ageLimit) {
            return response()->json([
                'message' => 'Password expired.',
                'require_change_password' => true,
            ], 403);
        }

        $user->update(['failed_logins' => 0, 'lockout_time' => null]);
       

        return response()->json($result, 200);
    }

    // ========================= VERIFY OTP =========================

    #[OA\Post(
        path: "/api/v1/verify-otp",
        operationId: "verifyOtp",
        summary: "Verify OTP",
        description: "Verify OTP and return API token",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "otp"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email"),
                    new OA\Property(property: "otp", type: "string", example: "123456")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "OTP verified successfully"),
            new OA\Response(response: 400, description: "Invalid OTP")
        ]
    )]
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required'],
        ]);

        $result = $this->authService->verifyOtp($request->email, $request->otp);

        if (!$result) {
            return response()->json(['message' => 'Invalid OTP.'], 400);
        }

        return response()->json($result, 200);
    }

    // ========================= LOGOUT =========================

    #[OA\Post(
        path: "/api/v1/logout",
        operationId: "logout",
        summary: "Logout",
        description: "Invalidate user token",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Logged out successfully"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    // ========================= FORGOT PASSWORD =========================

    #[OA\Post(
        path: "/api/v1/forgot-password",
        operationId: "forgotPassword",
        summary: "Forgot Password",
        description: "Send password reset link",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Reset link sent"),
            new OA\Response(response: 500, description: "Mail error")
        ]
    )]
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $result = $this->authService->sendPasswordResetLink($request->email);

        return response()->json(['message' => $result['message']], 200);
    }

    // ========================= RESET PASSWORD =========================

    #[OA\Post(
        path: "/api/v1/reset-password",
        operationId: "resetPassword",
        summary: "Reset Password",
        description: "Reset password using token",
        tags: ["Authentication"],
        responses: [
            new OA\Response(response: 200, description: "Password reset successfully"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $result = $this->authService->resetPassword(
            $request->only('email', 'password', 'password_confirmation', 'token')
        );

        return response()->json(
            ['message' => $result['message']], 
            $result['success'] ? 200 : 400
        );
    }

    // ========================= CHANGE INITIAL PASSWORD =========================

    #[OA\Post(
        path: "/api/v1/change-initial-password",
        operationId: "changeInitialPassword",
        summary: "Change Initial Password",
        tags: ["Authentication"],
        responses: [
            new OA\Response(response: 200, description: "Password changed successfully"),
            new OA\Response(response: 400, description: "Invalid credentials")
        ]
    )]
    public function changeInitialPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $result = $this->authService->changeInitialPassword(
            $request->email,
            $request->current_password,
            $request->password
        );

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json(['message' => $result['message']], 200);
    }

    // ========================= HELPERS =========================

    protected function logLoginAttempt($userId, $status, $ip, $message, $email)
    {
        LoginLog::create([
            'user_id' => $userId,
            'email' => $email,
            'status' => $status,
            'ip_address' => $ip,
            'message' => $message,
        ]);
    }
}
