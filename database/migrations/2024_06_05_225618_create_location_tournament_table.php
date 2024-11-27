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
		Schema::create('location_tournament', function (Blueprint $table) {
			$table->id();
			$table->foreignId('tournament_id')->constrained('tournaments')->onDelete('cascade');
			$table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
			$table->json('availability')->nullable();
			$table->softDeletes();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('location_tournament');
	}
};
