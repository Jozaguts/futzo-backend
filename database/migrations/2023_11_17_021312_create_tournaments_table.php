<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('tournaments', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('league_id')->nullable();
			$table->foreign('league_id')->references('id')->on('leagues');
			$table->unsignedBigInteger('category_id')->nullable();
			$table->foreign('category_id')->references('id')->on('categories');
			$table->unsignedBigInteger('tournament_format_id')->nullable();
			$table->foreign('tournament_format_id')->references('id')->on('tournament_formats');
			$table->foreignId('football_type_id')->constrained()->onDelete('restrict');
			$table->string('name');
			$table->string('image', 254)->nullable();
			$table->string('thumbnail', 254)->nullable();
			$table->date('start_date')->nullable();
			$table->date('end_date')->nullable();
			$table->string('prize')->nullable();
			$table->string('winner')->nullable();
			$table->longText('description')->nullable();
			$table->enum('status', ['creado', 'en curso', 'completado', 'cancelado'])->default('creado');
			$table->softDeletes();
			$table->timestamps();
			$table->index('league_id');
			$table->index('category_id');
			$table->index('tournament_format_id');
			$table->index('football_type_id');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('tournaments');
	}
};
