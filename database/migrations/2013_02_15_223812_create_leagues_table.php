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
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('football_type_id')->nullable();
            $table->foreign('football_type_id')->references('id')->on('football_types');
            $table->foreignId('country_id')->nullable()->references('id')->on('countries');
            $table->text('description')->nullable();
            $table->date('creation_date')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->string('status')->default('Activa');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leagues');
    }
};
