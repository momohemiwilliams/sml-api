<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Benchmark;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class BenchmarkController extends Controller
{
    /**
     * Fetch all benchmarks from the table
     */
    #[OA\Get(
        path: "/api/v1/benchmarks",
        operationId: "getBenchmarks",
        summary: "Get All Benchmarks",
        description: "Retrieve all benchmark records including their yes/no status",
        tags: ["Benchmarks"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful operation",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer"),
                            new OA\Property(property: "name", type: "string"),
                            new OA\Property(property: "value", type: "string", enum: ["yes", "no"]),
                            new OA\Property(property: "created_at", type: "string", format: "date-time"),
                            new OA\Property(property: "updated_at", type: "string", format: "date-time")
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(): JsonResponse
    {
        $benchmarks = Benchmark::all();
        return response()->json($benchmarks);
    }
}
