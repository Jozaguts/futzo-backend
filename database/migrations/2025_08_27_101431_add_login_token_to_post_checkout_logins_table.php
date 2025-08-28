<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('post_checkout_logins', function (Blueprint $table) {
            $table->text('login_token')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('post_checkout_logins', function (Blueprint $table) {
            $table->dropColumn('login_token');
        });
    }
};
