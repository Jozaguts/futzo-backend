<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plan')->default('free')->after('status');
            $table->unsignedInteger('tournaments_quota')->nullable()->after('plan');
            $table->unsignedInteger('tournaments_used')->default(0)->after('tournaments_quota');
            $table->timestamp('plan_started_at')->nullable()->after('tournaments_used');
            $table->timestamp('plan_expires_at')->nullable()->after('plan_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'plan',
                'tournaments_quota',
                'tournaments_used',
                'plan_started_at',
                'plan_expires_at',
            ]);
        });
    }
};
