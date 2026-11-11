<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TradingStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TradingStatusController extends Controller
{
    /**
     * Get all trading statuses
     *
     * @OA\Get(
     *     path="/api/v1/admin/trading-statuses",
     *     operationId="getTradingStatuses",
     *     summary="Get All Trading Statuses",
     *     description="Retrieve all trading statuses",
     *     tags={"Trading Statuses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filter by active status",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="code", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="is_active", type="boolean")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = TradingStatus::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $statuses = $query->orderBy('name')->get();

        return response()->json($statuses);
    }

    /**
     * Get a single trading status
     *
     * @OA\Get(
     *     path="/api/v1/admin/trading-statuses/{id}",
     *     operationId="getTradingStatus",
     *     summary="Get Trading Status by ID",
     *     tags={"Trading Statuses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Status not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $status = TradingStatus::findOrFail($id);
        return response()->json($status);
    }

    /**
     * Create a new trading status
     *
     * @OA\Post(
     *     path="/api/v1/admin/trading-statuses",
     *     operationId="createTradingStatus",
     *     summary="Create Trading Status",
     *     tags={"Trading Statuses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "code"},
     *             @OA\Property(property="name", type="string", example="Trading"),
     *             @OA\Property(property="code", type="string", example="TRADING"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="created_by", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Status created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:trading_statuses,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'created_by' => 'required|integer',
        ]);

        $status = TradingStatus::create($validated);

        return response()->json([
            'message' => 'Trading status created successfully',
            'data' => $status
        ], 201);
    }

    /**
     * Update a trading status
     *
     * @OA\Put(
     *     path="/api/v1/admin/trading-statuses/{id}",
     *     operationId="updateTradingStatus",
     *     summary="Update Trading Status",
     *     tags={"Trading Statuses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="updated_by", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Status updated"),
     *     @OA\Response(response=404, description="Status not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $status = TradingStatus::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:255|unique:trading_statuses,code,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'updated_by' => 'required|integer',
        ]);

        $status->update($validated);

        return response()->json([
            'message' => 'Trading status updated successfully',
            'data' => $status
        ]);
    }

    /**
     * Delete a trading status
     *
     * @OA\Delete(
     *     path="/api/v1/admin/trading-statuses/{id}",
     *     operationId="deleteTradingStatus",
     *     summary="Delete Trading Status",
     *     tags={"Trading Statuses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Status deleted"),
     *     @OA\Response(response=404, description="Status not found")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $status = TradingStatus::findOrFail($id);
        $status->delete();

        return response()->json([
            'message' => 'Trading status deleted successfully'
        ]);
    }
}
