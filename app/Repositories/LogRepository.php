<?php

namespace App\Repositories;

use App\Models\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class LogRepository implements LogRepositoryInterface
{
    /**
     * Create a new log entry.
     *
     * @param array<string, mixed> $data
     * @return Log
     */
    public function create(array $data): Log
    {
        return Log::create($data);
    }

    /**
     * Find a log by ID.
     *
     * @param string $id
     * @return Log|null
     */
    public function findById(string $id): ?Log
    {
        return Log::find($id);
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
        $query = Log::query();

        if (isset($filters['trace_id']) && !empty($filters['trace_id'])) {
            $query->where('trace_id', $filters['trace_id']);
        }

        if (isset($filters['level']) && !empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (isset($filters['service_name']) && !empty($filters['service_name'])) {
            $query->where('service_name', $filters['service_name']);
        }

        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $query->where('timestamp', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $query->where('timestamp', '<=', $filters['date_to']);
        }

        return $query->orderBy('timestamp', 'desc')->paginate($perPage);
    }
}
