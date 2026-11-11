<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListingStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ListingStatusController extends Controller
{
    /**
     * Get all listing statuses
     *
     * @OA\Get(
     *     path="/api/v1/admin/listing-statuses",
     *     operationId="getListingStatuses",
     *     summary="Get All Listing Statuses",
     *     description="Retrieve all listing statuses",
     *     tags={"Listing Statuses"},
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
        $query = ListingStatus::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $statuses = $query->orderBy('name')->get();

        return response()->json($statuses);
    }

    /**
     * Get a single listing status
     *
     * @OA\Get(
     *     path="/api/v1/admin/listing-statuses/{id}",
     *     operationId="getListingStatus",
     *     summary="Get Listing Status by ID",
     *     tags={"Listing Statuses"},
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
        $status = ListingStatus::findOrFail($id);
        return response()->json($status);
    }

    /**
     * Create a new listing status
     *
     * @OA\Post(
     *     path="/api/v1/admin/listing-statuses",
     *     operationId="createListingStatus",
     *     summary="Create Listing Status",
     *     tags={"Listing Statuses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "code"},
     *             @OA\Property(property="name", type="string", example="Fully Listed"),
     *             @OA\Property(property="code", type="string", example="FL"),
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
            'code' => 'required|string|max:255|unique:listing_statuses,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'created_by' => 'required|integer',
        ]);

        $status = ListingStatus::create($validated);

        return response()->json([
            'message' => 'Listing status created successfully',
            'data' => $status
        ], 201);
    }

    /**
     * Update a listing status
     *
     * @OA\Put(
     *     path="/api/v1/admin/listing-statuses/{id}",
     *     operationId="updateListingStatus",
     *     summary="Update Listing Status",
     *     tags={"Listing Statuses"},
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
        $status = ListingStatus::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:255|unique:listing_statuses,code,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'updated_by' => 'required|integer',
        ]);

        $status->update($validated);

        return response()->json([
            'message' => 'Listing status updated successfully',
            'data' => $status
        ]);
    }

    /**
     * Delete a listing status
     *
     * @OA\Delete(
     *     path="/api/v1/admin/listing-statuses/{id}",
     *     operationId="deleteListingStatus",
     *     summary="Delete Listing Status",
     *     tags={"Listing Statuses"},
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
        $status = ListingStatus::findOrFail($id);
        $status->delete();

        return response()->json([
            'message' => 'Listing status deleted successfully'
        ]);
    }
}
