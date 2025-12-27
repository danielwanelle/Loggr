<?php

namespace App\Jobs;

use App\Models\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ProcessLogEntry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly array $logData
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Garante que trace_id e _id sejam UUIDs
        $data = $this->logData;
        
        if (!isset($data['_id']) || empty($data['_id'])) {
            $data['_id'] = (string) Str::uuid();
        }
        
        if (!isset($data['trace_id']) || empty($data['trace_id'])) {
            $data['trace_id'] = (string) Str::uuid();
        }
        
        if (!isset($data['timestamp']) || empty($data['timestamp'])) {
            $data['timestamp'] = now();
        }

        Log::create($data);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Log do erro se necessário
        \Illuminate\Support\Facades\Log::error('Failed to process log entry', [
            'data' => $this->logData,
            'exception' => $exception->getMessage(),
        ]);
    }
}
