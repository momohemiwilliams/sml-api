<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\OptionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OptionTypeController extends Controller
{
    /**
     * Get all option types
     *
     * @OA\Get(
     *     path="/api/v1/admin/option-types",
     *     operationId="getOptionTypes",
     *     summary="Get All Option Types",
     *     description="Retrieve all option types",
     *     tags={"Option Types"},
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
     *                 @OA\Property(property="has_call_date", type="boolean"),
     *                 @OA\Property(property="is_active", type="boolean")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = OptionType::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $optionTypes = $query->orderBy('name')->get();

        return response()->json($optionTypes);
    }

    /**
     * Get a single option type
     *
     * @OA\Get(
     *     path="/api/v1/admin/option-types/{id}",
     *     operationId="getOptionType",
     *     summary="Get Option Type by ID",
     *     tags={"Option Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Option type not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $optionType = OptionType::findOrFail($id);
        return response()->json($optionType);
    }

    /**
     * Create a new option type
     *
     * @OA\Post(
     *     path="/api/v1/admin/option-types",
     *     operationId="createOptionType",
     *     summary="Create Option Type",
     *     tags={"Option Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "code"},
     *             @OA\Property(property="name", type="string", example="Callable"),
     *             @OA\Property(property="code", type="string", example="CALLABLE"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="has_call_date", type="boolean", example=true),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="created_by", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Option type created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:option_types,code',
            'description' => 'nullable|string',
            'has_call_date' => 'boolean',
            'is_active' => 'boolean',
            'created_by' => 'required|integer',
        ]);

        $optionType = OptionType::create($validated);

        return response()->json([
            'message' => 'Option type created successfully',
            'data' => $optionType
        ], 201);
    }

    /**
     * Update an option type
     *
     * @OA\Put(
     *     path="/api/v1/admin/option-types/{id}",
     *     operationId="updateOptionType",
     *     summary="Update Option Type",
     *     tags={"Option Types"},
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
     *             @OA\Property(property="has_call_date", type="boolean"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="updated_by", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Option type updated"),
     *     @OA\Response(response=404, description="Option type not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $optionType = OptionType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:255|unique:option_types,code,' . $id,
            'description' => 'nullable|string',
            'has_call_date' => 'boolean',
            'is_active' => 'boolean',
            'updated_by' => 'required|integer',
        ]);

        $optionType->update($validated);

        return response()->json([
            'message' => 'Option type updated successfully',
            'data' => $optionType
        ]);
    }

    /**
     * Delete an option type
     *
     * @OA\Delete(
     *     path="/api/v1/admin/option-types/{id}",
     *     operationId="deleteOptionType",
     *     summary="Delete Option Type",
     *     tags={"Option Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Option type deleted"),
     *     @OA\Response(response=404, description="Option type not found")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $optionType = OptionType::findOrFail($id);
        $optionType->delete();

        return response()->json([
            'message' => 'Option type deleted successfully'
        ]);
    }
}
