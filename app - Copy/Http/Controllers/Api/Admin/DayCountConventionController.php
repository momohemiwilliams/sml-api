<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DayCountConvention;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DayCountConventionController extends Controller
{
    /**
     * Get all day count conventions
     *
     * @OA\Get(
     *     path="/api/v1/admin/day-count-conventions",
     *     operationId="getDayCountConventions",
     *     summary="Get All Day Count Conventions",
     *     description="Retrieve all day count conventions",
     *     tags={"Day Count Conventions"},
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
        $query = DayCountConvention::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $conventions = $query->orderBy('name')->get();

        return response()->json($conventions);
    }

    /**
     * Get a single day count convention
     *
     * @OA\Get(
     *     path="/api/v1/admin/day-count-conventions/{id}",
     *     operationId="getDayCountConvention",
     *     summary="Get Day Count Convention by ID",
     *     tags={"Day Count Conventions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Convention not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $convention = DayCountConvention::findOrFail($id);
        return response()->json($convention);
    }

    /**
     * Create a new day count convention
     *
     * @OA\Post(
     *     path="/api/v1/admin/day-count-conventions",
     *     operationId="createDayCountConvention",
     *     summary="Create Day Count Convention",
     *     tags={"Day Count Conventions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "code"},
     *             @OA\Property(property="name", type="string", example="Actual/Actual"),
     *             @OA\Property(property="code", type="string", example="ACT_ACT"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="created_by", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Convention created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:day_count_conventions,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'created_by' => 'required|integer',
        ]);

        $convention = DayCountConvention::create($validated);

        return response()->json([
            'message' => 'Day count convention created successfully',
            'data' => $convention
        ], 201);
    }

    /**
     * Update a day count convention
     *
     * @OA\Put(
     *     path="/api/v1/admin/day-count-conventions/{id}",
     *     operationId="updateDayCountConvention",
     *     summary="Update Day Count Convention",
     *     tags={"Day Count Conventions"},
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
     *     @OA\Response(response=200, description="Convention updated"),
     *     @OA\Response(response=404, description="Convention not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $convention = DayCountConvention::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:255|unique:day_count_conventions,code,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'updated_by' => 'required|integer',
        ]);

        $convention->update($validated);

        return response()->json([
            'message' => 'Day count convention updated successfully',
            'data' => $convention
        ]);
    }

    /**
     * Delete a day count convention
     *
     * @OA\Delete(
     *     path="/api/v1/admin/day-count-conventions/{id}",
     *     operationId="deleteDayCountConvention",
     *     summary="Delete Day Count Convention",
     *     tags={"Day Count Conventions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Convention deleted"),
     *     @OA\Response(response=404, description="Convention not found")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $convention = DayCountConvention::findOrFail($id);
        $convention->delete();

        return response()->json([
            'message' => 'Day count convention deleted successfully'
        ]);
    }
}
