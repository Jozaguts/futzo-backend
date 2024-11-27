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
		Schema::create('football_types', function (Blueprint $table) {
			$table->id();
			$table->string('name');
			$table->text('description');
			$table->string('status')->default('created');
			$table->unsignedInteger('max_players_per_team')->default(11); // Máximo jugadores en el campo
			$table->unsignedInteger('min_players_per_team')->default(7); // Mínimo jugadores permitidos
			$table->unsignedInteger('max_registered_players')->default(23); // Máximo jugadores registrados
			$table->unsignedInteger('substitutions')->nullable(); // Sustituciones permitidas, null para ilimitado
			$table->softDeletes();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('football_types');
	}
};
