<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('string');
            $table->integer('qty_total')->default(-1);
            $table->date('initial_date')->default(null);
            $table->date('end_date')->default(null);
            $table->enum('factor_type',['percentage','amount','promotion'])->default('percentage');
            $table->decimal('factor_value')->default(0);
            $table->boolean('accept_same_email')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
