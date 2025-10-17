<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('sender_id')
                ->constrained('users')
                ->onDelete('restrict');
            $table
                ->foreignId('receiver_id')
                ->constrained('users')
                ->onDelete('restrict');
            $table->decimal('amount', 10, 2);
            $table->string('description')->nullable();
            $table
                ->enum('status', ['pending', 'completed', 'failed'])
                ->default('completed');
            $table->string('transaction_hash')->unique();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index('sender_id');
            $table->index('receiver_id');
            $table->index('created_at');
            $table->index('transaction_hash');
        });

        // CHECK constraint para MySQL 8.0.16+
        DB::statement(
            'ALTER TABLE transactions ADD CONSTRAINT check_amount_positive CHECK (amount > 0)'
        );
        DB::statement(
            'ALTER TABLE transactions ADD CONSTRAINT check_different_users CHECK (sender_id != receiver_id)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
