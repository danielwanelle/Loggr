<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexLogRequest;
use App\Http\Requests\StoreLogRequest;
use App\Http\Resources\LogResource;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LogController extends Controller
{
    /**
     * Create a new LogController instance.
     *
     * @param LogService $logService
     */
    public function __construct(
        private readonly LogService $logService
    ) {}

    /**
     * Display a listing of logs with optional filters.
     *
     * @param IndexLogRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(IndexLogRequest $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'trace_id',
            'level',
            'service_name',
            'date_from',
            'date_to',
        ]);

        $perPage = $request->input('per_page', 15);

        $logs = $this->logService->search($filters, $perPage);

        return LogResource::collection($logs);
    }

    /**
     * Store a newly created log in storage asynchronously.
     *
     * @param StoreLogRequest $request
     * @return JsonResponse
     */
    public function store(StoreLogRequest $request): JsonResponse
    {
        $ids = $this->logService->createAsync($request->validated());

        return response()->json([
            'message' => 'Log será processado em breve.',
            'id' => $ids['id'],
            'trace_id' => $ids['trace_id'],
        ], 202);
    }

    /**
     * Display the specified log.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $log = $this->logService->findById($id);

        if (!$log) {
            return response()->json([
                'message' => 'Log não encontrado.',
            ], 404);
        }

        return (new LogResource($log))->response();
    }
}
