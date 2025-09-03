<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('fbp')->nullable()->after('google_id');
            $table->string('fbc')->nullable()->after('fbp');
            $table->string('fbclid')->nullable()->after('fbc');
            $table->boolean('capi_consent')->nullable()->after('fbclid');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['fbp', 'fbc', 'fbclid', 'capi_consent']);
        });
    }
};

