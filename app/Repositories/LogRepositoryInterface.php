<?php

namespace App\Repositories;

use App\Models\Log;
use Illuminate\Pagination\LengthAwarePaginator;

interface LogRepositoryInterface
{
    /**
     * Create a new log entry.
     *
     * @param array<string, mixed> $data
     * @return Log
     */
    public function create(array $data): Log;

    /**
     * Find a log by ID.
     *
     * @param string $id
     * @return Log|null
     */
    public function findById(string $id): ?Log;

    /**
     * Search logs with filters.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;
}
