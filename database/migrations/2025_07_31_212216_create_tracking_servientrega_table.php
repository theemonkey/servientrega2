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
        Schema::create('tracking_servientrega', function (Blueprint $table) {
            $table->id();
            $table->string('numero_guia');
            $table->string('estado')->nullable();
            $table->string('ciudad')->nullable();
            $table->date('fecha')->nullable();
            $table->json('respuesta')->nullable();
            $table->timestamps();

            $table->index('numero_guia'); // Index para mejorar la búsqueda por número de guía
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_servientrega');
    }
};
