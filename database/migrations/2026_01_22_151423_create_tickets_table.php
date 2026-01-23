<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('league_id')->constrained('leagues')->cascadeOnDelete();
            $table->foreignId('tournament_id')->nullable()->constrained('tournaments')->nullOnDelete();

            $table->enum('contact_method',[ 'email', 'phone'])->default('email');
            $table->string('contact_value', 190);
            // Para listas, filtros y contexto rápido
            $table->string('subject', 190);

            // Tags/Badges (3)
            $table->enum('category', ['bug', 'support', 'feature'])->index();

            // Estado del ticket (simple y suficiente)
            $table->enum('status', ['open', 'pending', 'answered', 'closed'])
                ->default('open')
                ->index();

            // Prioridad (útil para soporte)
            $table->enum('priority', ['low', 'normal', 'high'])
                ->default('normal')
                ->index();

            // Timestamps de operación
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable();

            // Extras sin otra tabla (ej: device_id, page_url, browser, etc.)
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices comunes en listados
            $table->index(['user_id', 'status']);
            $table->index(['category', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
