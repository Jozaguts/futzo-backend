<?php

use App\Models\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('currencies')) {
            Schema::create('currencies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('symbol');
                $table->string('iso_code');
                $table->string('payment_gateway');
                $table->boolean('is_default');
                $table->json('properties')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
            $cur = [
                [
                    'iso_code' => 'MXN',
                    'symbol' => '$',
                    'name' => 'Peso Mexicano',
                    'payment_gateway' => 'Stripe',
                    'is_default' => true,
                ],
//                [
//                    'name' => 'US Dolar',
//                    'symbol' => '$',
//                    'iso_code' => 'USD',
//                    'payment_gateway' => 'Stripe',
//                    'is_default' => false,
//                ],
//                [
//                    'name' => 'Peso Chileno',
//                    'symbol' => '$',
//                    'iso_code' => 'CLP',
//                    'payment_gateway' => 'Stripe',
//                    'is_default' => false,
//                ],
//                [
//                    'name' => 'Euro',
//                    'symbol' => '€',
//                    'iso_code' => 'EUR',
//                    'payment_gateway' => 'Stripe',
//                    'is_default' => false,
//                ],
//                [
//                    'iso_code' => 'ARS',
//                    'symbol' => '$',
//                    'name' => 'Peso Argentina',
//                    'payment_gateway' => 'Stripe',
//                    'is_default' => false,
//                ]
            ];
            foreach ($cur as $c) {
                Currency::updateOrCreate([
                    'iso_code' => $c['iso_code'],
                ],[
                    'name' => $c['name'],
                    'symbol' => $c['symbol'],
                    'payment_gateway' => $c['payment_gateway'],
                    'is_default' => $c['is_default'],

                ]);
            }
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
