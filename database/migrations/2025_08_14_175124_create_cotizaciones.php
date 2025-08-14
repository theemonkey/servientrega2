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
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->integer('tipo_servicio')->nullable();
            $table->integer('tipo_empaque')->nullable();
            $table->float('peso_fisico')->nullable();
            $table->float('peso_volumen')->nullable();
            $table->float('largo')->nullable();
            $table->float('ancho')->nullable();
            $table->float('alto')->nullable();
            $table->float('valor_declarado')->nullable();
            $table->float('costo_flete')->nullable();
            $table->float('valor_sobretasa')->nullable();
            $table->float('valor_total')->nullable();

            // Definición de las claves foráneas
            $table->foreignID('guia_id')->nullable()->constrained('guias');
            $table->foreignId('origen_id')->nullable()->constrained('ciudades');
            $table->foreignId('destino_id')->nullable()->constrained('ciudades');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
