<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscription', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained('users');
            $table->string('stripe_subscription_id')->nullable()->index();
            $table->enum('plan',['kickoff','pro_play','elite']);
            $table->enum('benefit',['p299','p699','none']);
            $table->enum('status',['active','trialing','past_due','canceled','incomplete']);
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription');
    }
};
