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
		Schema::create('users', function (Blueprint $table) {
			$table->id();
			$table->string('name');
			$table->string('last_name')->nullable();
			$table->string('email')->unique()->nullable();
			$table->string('verification_token', 25)->nullable();
			$table->string('phone', 20)->unique()->nullable();
			$table->timestamp('verified_at')->nullable();
			$table->string('image')->nullable();
			$table->string('password')->nullable();
			$table->string('facebook_id')->nullable();
			$table->string('google_id')->nullable();
			$table->unsignedBigInteger('league_id')->nullable();
			$table->foreign('league_id')->references('id')->on('leagues');
			$table->softDeletes();
			$table->rememberToken();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('users');
	}
};
