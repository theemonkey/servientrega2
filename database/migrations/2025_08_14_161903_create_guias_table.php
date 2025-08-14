<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ingresar campos de la tabla guias.
     */
    public function up(): void
    {
        Schema::create('guias', function (Blueprint $table) {
            $table->id();
            $table->string('numero_guia',50)->unique();
            $table->date('fecha_envio')->nullable();
            $table->integer('numero_piezas')->nullable();
            $table->string('remitente_nombre')->nullable();
            $table->string('remitente_direccion')->nullable();
            $table->string('destinatario_nombre')->nullable();
            $table->string('destinatario_direccion')->nullable();
            $table->dateTime('fecha_probable_entrega')->nullable();
            $table->string('regimen', 50)->nullable();

             // Definición de las claves foráneas
            $table->foreignId('estado_actual_id')->nullable()->constrained('estados');
            $table->foreignId('ciudad_remitente_id')->nullable()->constrained('ciudades');
            $table->foreignId('ciudad_destino_id')->nullable()->constrained('ciudades');
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guias');
    }
};
