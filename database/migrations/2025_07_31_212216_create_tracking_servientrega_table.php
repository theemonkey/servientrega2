<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_servientrega', function (Blueprint $table) {
            $table->id();

            // Información básica de la guía
            $table->string('numero_guia')->nullable();
            $table->timestamp('fec_env')->nullable();
            $table->integer('num_pie')->nullable();

            // Información del remitente
            $table->string('ciu_remitente')->nullable();
            $table->string('nom_remitente')->nullable();
            $table->string('dir_remitente')->nullable();

            // Información del destinatario
            $table->string('ciu_destinatario')->nullable();
            $table->string('nom_destinatario')->nullable();
            $table->string('dir_destinatario')->nullable();

            // Estado del envío
            $table->integer('id_estado_actual')->nullable();
            $table->string('estado_actual')->nullable();
            $table->timestamp('fecha_estado')->nullable();

            // Información de entrega
            $table->string('nom_receptor')->nullable();
            $table->integer('num_cun')->nullable();

            // Información logística
            $table->string('regimen')->nullable();
            $table->string('placa')->nullable();
            $table->integer('id_gps')->nullable();
            $table->string('forma_pago')->nullable();
            $table->string('nomb_producto')->nullable();
            $table->timestamp('fecha_probable')->nullable();

            // Historial de movimientos
            $table->json('movimientos')->nullable();

            // IMAGEN COMO BASE64 (LONGTEXT para soportar TIFF)
            $table->longText('imagen_base64')->nullable();

            $table->timestamps();

            /* // Índices
            $table->index('numero_guia');
            $table->index('id_estado_actual');
            $table->index('fecha_estado');
            $table->index('fec_env');*/
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_servientrega');
    }
};
