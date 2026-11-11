<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ExternalUserService;
use Illuminate\Http\JsonResponse;

class TestUserServiceController extends Controller
{
    protected $externalUserService;

    public function __construct(ExternalUserService $externalUserService)
    {
        $this->externalUserService = $externalUserService;
    }

    /**
     * Test endpoint to check external user service
     */
    public function testUsers(): JsonResponse
    {
        $allUsers = $this->externalUserService->getAllUsers();
        
        $user19 = $this->externalUserService->getUserById(19);
        $user20 = $this->externalUserService->getUserById(20);
        
        return response()->json([
            'total_users' => $allUsers->count(),
            'user_19' => $user19,
            'user_20' => $user20,
            'all_user_ids' => $allUsers->keys()->take(20)->values(),
            'sample_users' => $allUsers->take(5)->values(),
        ]);
    }
}
