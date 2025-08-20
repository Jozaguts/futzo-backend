<?php

use App\Models\Product;
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
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('sku')->unique();
                $table->string('name');
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });
            $products = [
                [
                    'id' => 1,
                    'name' => 'Kickoff',
                    'sku' => 'kickoff',
                    'is_active' => true,
                    'description' => 'Gestión básica de torneos y partidos automatizados ',

                ],
                [
                    'id' => 2,
                    'name' => 'ProPlay',
                    'sku' => 'pro_play',
                    'is_active' => true,
                    'description' => 'Kickoff + Registro automático URLs únicas, Configuración visual, Soporte incluido  ',

                ],
                [
                    'id' => 3,
                    'name' => 'EliteLeague',
                    'sku' => 'elite_league',
                    'is_active' => true,
                    'description' => 'ProPlay + Soporte prioritario, Acceso anticipado a  nuevas funciones, Comunicación directa',

                ]
            ];
            foreach ($products as $product) {
                Product::updateOrCreate([
                    'sku' => $product['sku'],
                ],[
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'is_active' => $product['is_active'],
                ]);
            }

        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
