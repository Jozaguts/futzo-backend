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
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();

            $table->uuid('ticket_id')->index();
            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
                ->cascadeOnDelete();

            // Quién escribió (usuario / staff / sistema)
            $table->enum('author_type', ['user', 'staff', 'system'])->index();

            // Para user|staff apunta a users.id (system = null)
            $table->foreignId('author_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('body');

            // Adjuntos futuros: [{name,url,size,mime}] o similar
            $table->json('attachments')->nullable();

            // Nota interna (solo staff)
            $table->boolean('is_internal')->default(false)->index();

            // Lectura (si luego quieres “unread”)
            $table->timestamp('read_at')->nullable()->index();

            $table->timestamps();

            // Para paginar/conversación
            $table->index(['ticket_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};
