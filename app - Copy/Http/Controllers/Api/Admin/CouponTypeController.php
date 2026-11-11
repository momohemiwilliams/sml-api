<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CouponType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CouponTypeController extends Controller
{
    /**
     * Get all coupon types
     *
     * @OA\Get(
     *     path="/api/v1/admin/coupon-types",
     *     operationId="getCouponTypes",
     *     summary="Get All Coupon Types",
     *     description="Retrieve all coupon types",
     *     tags={"Coupon Types"},
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
        $query = CouponType::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $couponTypes = $query->orderBy('name')->get();

        return response()->json($couponTypes);
    }

    /**
     * Get a single coupon type
     *
     * @OA\Get(
     *     path="/api/v1/admin/coupon-types/{id}",
     *     operationId="getCouponType",
     *     summary="Get Coupon Type by ID",
     *     tags={"Coupon Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Coupon type not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $couponType = CouponType::findOrFail($id);
        return response()->json($couponType);
    }

    /**
     * Create a new coupon type
     *
     * @OA\Post(
     *     path="/api/v1/admin/coupon-types",
     *     operationId="createCouponType",
     *     summary="Create Coupon Type",
     *     tags={"Coupon Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "code"},
     *             @OA\Property(property="name", type="string", example="Floating Rate Margin (FRM)"),
     *             @OA\Property(property="code", type="string", example="FRM"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="created_by", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Coupon type created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:coupon_types,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'created_by' => 'required|integer',
        ]);

        $couponType = CouponType::create($validated);

        return response()->json([
            'message' => 'Coupon type created successfully',
            'data' => $couponType
        ], 201);
    }

    /**
     * Update a coupon type
     *
     * @OA\Put(
     *     path="/api/v1/admin/coupon-types/{id}",
     *     operationId="updateCouponType",
     *     summary="Update Coupon Type",
     *     tags={"Coupon Types"},
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
     *     @OA\Response(response=200, description="Coupon type updated"),
     *     @OA\Response(response=404, description="Coupon type not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $couponType = CouponType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:255|unique:coupon_types,code,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'updated_by' => 'required|integer',
        ]);

        $couponType->update($validated);

        return response()->json([
            'message' => 'Coupon type updated successfully',
            'data' => $couponType
        ]);
    }

    /**
     * Delete a coupon type
     *
     * @OA\Delete(
     *     path="/api/v1/admin/coupon-types/{id}",
     *     operationId="deleteCouponType",
     *     summary="Delete Coupon Type",
     *     tags={"Coupon Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Coupon type deleted"),
     *     @OA\Response(response=404, description="Coupon type not found")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $couponType = CouponType::findOrFail($id);
        $couponType->delete();

        return response()->json([
            'message' => 'Coupon type deleted successfully'
        ]);
    }
}
