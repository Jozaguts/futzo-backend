<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('phone_password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
};
