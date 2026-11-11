<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CouponFrequency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CouponFrequencyController extends Controller
{
    /**
     * Get all coupon frequencies
     *
     * @OA\Get(
     *     path="/api/v1/admin/coupon-frequencies",
     *     operationId="getCouponFrequencies",
     *     summary="Get All Coupon Frequencies",
     *     description="Retrieve all coupon frequencies",
     *     tags={"Coupon Frequencies"},
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
     *                 @OA\Property(property="frequency_per_year", type="integer"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="is_active", type="boolean")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = CouponFrequency::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $frequencies = $query->orderBy('frequency_per_year')->get();

        return response()->json($frequencies);
    }

    /**
     * Get a single coupon frequency
     *
     * @OA\Get(
     *     path="/api/v1/admin/coupon-frequencies/{id}",
     *     operationId="getCouponFrequency",
     *     summary="Get Coupon Frequency by ID",
     *     tags={"Coupon Frequencies"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Frequency not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $frequency = CouponFrequency::findOrFail($id);
        return response()->json($frequency);
    }

    /**
     * Create a new coupon frequency
     *
     * @OA\Post(
     *     path="/api/v1/admin/coupon-frequencies",
     *     operationId="createCouponFrequency",
     *     summary="Create Coupon Frequency",
     *     tags={"Coupon Frequencies"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "code", "frequency_per_year"},
     *             @OA\Property(property="name", type="string", example="Quarterly"),
     *             @OA\Property(property="code", type="string", example="QUARTERLY"),
     *             @OA\Property(property="frequency_per_year", type="integer", example=4),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="created_by", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Frequency created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:coupon_frequencies,code',
            'frequency_per_year' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'created_by' => 'required|integer',
        ]);

        $frequency = CouponFrequency::create($validated);

        return response()->json([
            'message' => 'Coupon frequency created successfully',
            'data' => $frequency
        ], 201);
    }

    /**
     * Update a coupon frequency
     *
     * @OA\Put(
     *     path="/api/v1/admin/coupon-frequencies/{id}",
     *     operationId="updateCouponFrequency",
     *     summary="Update Coupon Frequency",
     *     tags={"Coupon Frequencies"},
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
     *             @OA\Property(property="frequency_per_year", type="integer"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="updated_by", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Frequency updated"),
     *     @OA\Response(response=404, description="Frequency not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $frequency = CouponFrequency::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:255|unique:coupon_frequencies,code,' . $id,
            'frequency_per_year' => 'sometimes|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'updated_by' => 'required|integer',
        ]);

        $frequency->update($validated);

        return response()->json([
            'message' => 'Coupon frequency updated successfully',
            'data' => $frequency
        ]);
    }

    /**
     * Delete a coupon frequency
     *
     * @OA\Delete(
     *     path="/api/v1/admin/coupon-frequencies/{id}",
     *     operationId="deleteCouponFrequency",
     *     summary="Delete Coupon Frequency",
     *     tags={"Coupon Frequencies"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Frequency deleted"),
     *     @OA\Response(response=404, description="Frequency not found")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $frequency = CouponFrequency::findOrFail($id);
        $frequency->delete();

        return response()->json([
            'message' => 'Coupon frequency deleted successfully'
        ]);
    }
}
