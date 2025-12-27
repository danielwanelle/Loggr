<?php

namespace App\Services;

use App\Jobs\ProcessLogEntry;
use App\Models\Log;
use App\Repositories\LogRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class LogService
{
    /**
     * Create a new LogService instance.
     *
     * @param LogRepositoryInterface $logRepository
     */
    public function __construct(
        private readonly LogRepositoryInterface $logRepository
    ) {}

    /**
     * Create a new log entry asynchronously.
     *
     * @param array<string, mixed> $data
     * @return array<string, string> Returns the generated IDs
     */
    public function createAsync(array $data): array
    {
        // Gera UUIDs antes de enviar para a fila
        $traceId = $data['trace_id'] ?? (string) Str::uuid();
        $id = (string) Str::uuid();
        
        $data['_id'] = $id;
        $data['trace_id'] = $traceId;
        
        if (!isset($data['timestamp'])) {
            $data['timestamp'] = now()->toIso8601String();
        }

        // Despacha o job para processamento assíncrono
        ProcessLogEntry::dispatch($data);

        return [
            'id' => $id,
            'trace_id' => $traceId,
        ];
    }

    /**
     * Find a log by ID.
     *
     * @param string $id
     * @return Log|null
     */
    public function findById(string $id): ?Log
    {
        return $this->logRepository->findById($id);
    }

    /**
     * Search logs with filters.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->logRepository->search($filters, $perPage);
    }
}
