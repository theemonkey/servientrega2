<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guias_envio', function (Blueprint $table){
            $table->id();
            $table->unsignedBigInteger('user_id');

            //Campos principales de la respuesta API
            $table->string('num_guia')->nullable()->unique();
            $table->integer('num_sobreporte')->default(0); //opcional
            $table->integer('num_sobre_caja_porte')->default(0); //opcional
            $table->integer('num_piezas')->nullable();
            $table->integer('des_tipo_trayecto')->nullable();
            $table->integer('ide_producto')->nullable();
            $table->integer('ide_destinatarios')->nullable();
            $table->string('ide_manifiesto')->nullable();
            $table->integer('des_forma_pago')->nullable();
            $table->integer('des_medio_transporte')->nullable();
            $table->decimal('num_peso_total', 10, 2)->nullable();
            $table->decimal('num_valor_declarado_total', 12, 2)->nullable();
            $table->decimal('num_volumen_total', 10, 2)->nullable();
            $table->integer('num_bolsa_seguridad')->nullable();
            $table->integer('num_precinto')->nullable();
            $table->integer('des_tipo_duracion_trayecto')->nullable();

            //Datos de contacto y direccion
            $table->integer('des_telefono')->nullable();
            $table->string('des_ciudad')->nullable();
            $table->string('des_direccion')->nullable();
            $table->string('nom_contacto')->nullable();
            $table->string('des_valor_campo_personalizado')->nullable();
            $table->decimal('num_valor_liquidado', 12, 2)->nullable();
            $table->text('des_dice_contener')->nullable();
            $table->integer('des_tipo_guia')->nullable();
            $table->decimal('num_valor_sobre_flete', 12, 2)->nullable();
            $table->decimal('num_valor_flete', 12, 2)->nullable();
            $table->decimal('num_descuento', 12, 2)->nullable();
            $table->integer('id_pais_origen')->required()->default(1);
            $table->integer('id_pais_destino')->required()->default(1);
            $table->integer('des_id_archivo_origen')->default(0);
            $table->string('des_direccion_remitente')->nullable();
            $table->decimal('num_peso_facturado',10,2)->nullable();
            $table->boolean('est_canal_mayorista')->default(false);
            $table->string('num_identi_remitente')->nullable();
            $table->string('num_telefono_remitente')->nullable();
            $table->decimal('num_alto',8,2)->nullable();
            $table->decimal('num_ancho',8,2)->nullable();
            $table->decimal('num_largo',8,2)->nullable();
            $table->string('des_departamento_destino')->nullable();
            $table->boolean('gen_caja_porte')->default(false);
            $table->boolean('gen_sobre_porte')->default(false);
            $table->string('nom_unidad_empaque')->nullable();
            $table->string('des_correo_electronico')->nullable();
            $table->string('id_archivo_cargar')->nullable();
            $table->boolean('est_enviar_correo')->default(false);
            $table->integer('retorno_digital')->default(0);
            
            //Control de estado
            $table->enum('estado', ['borrador', 'procesando', 'generada', 'error'])->default('borrador');
            $table->text('mensaje_error')->nullable();
            $table->json('respuesta_completa_api')->nullable();
            $table->timestamp('fecha_generacion')->nullable();

            //Metadatos adicionales
            $table->string('referencia_cliente')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();

            //Indices y relaciones
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'created_at']);
            $table->index(['num_guia']);
            $table->index(['estado']);
        });
    }
   
    public function down(): void
    {
        Schema::dropIfExists('guias_envio');
    }
};
