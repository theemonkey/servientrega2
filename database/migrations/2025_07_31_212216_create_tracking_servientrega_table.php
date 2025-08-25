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
            $table->string('numero_guia')->nullable();
            $table->timestamp('fec_env')->nullable();
            $table->integer('num_pie')->nullable();
            $table->string('ciu_remitente')->nullable();
            $table->string('nom_remitente')->nullable();
            $table->string('dir_remitente')->nullable();
            $table->string('ciu_destinatario')->nullable();
            $table->string('nom_destinatario')->nullable();
            $table->string('dir_destinatario')->nullable();
            $table->integer('id_estado_actual')->nullable();
            $table->string('estado_actual')->nullable();
            $table->timestamp('fecha_estado')->nullable();
            $table->string('nom_receptor')->nullable();
            $table->integer('num_cun')->nullable();
            $table->string('regimen')->nullable();
            $table->string('placa')->nullable();
            $table->integer('id_gps')->nullable();
            $table->string('forma_pago')->nullable();
            $table->string('nomb_producto')->nullable();
            $table->timestamp('fecha_probable')->nullable();
            $table->json('movimientos')->nullable(); 
            $table->longText('imagen_base64')->nullable();
            $table->timestamps();
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
