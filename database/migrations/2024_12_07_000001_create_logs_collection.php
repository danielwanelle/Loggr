<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mongodb')->create('logs', function ($collection) {
            // Índices para os campos de busca
            $collection->index('trace_id');
            $collection->index('level');
            $collection->index('service_name');
            $collection->index('timestamp');
            
            // Índice composto para buscas mais complexas
            $collection->index([
                'trace_id' => 1,
                'timestamp' => -1,
            ]);
            
            $collection->index([
                'service_name' => 1,
                'level' => 1,
                'timestamp' => -1,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('logs');
    }
};
