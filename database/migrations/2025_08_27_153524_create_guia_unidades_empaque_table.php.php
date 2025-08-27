<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('guia_unidades_empaque', function (Blueprint $table){
            $table->id();
            $table->unsignedBigInteger('guia_envio_id');

            //Datos de la API response
             $table->decimal('num_alto', 8, 2);
            $table->integer('num_distribuidor')->default(0);
            $table->decimal('num_ancho', 8, 2);
            $table->integer('num_cantidad');
            $table->text('des_dice_contener');
            $table->string('des_id_archivo_origen')->default('0');
            $table->decimal('num_largo', 8, 2);
            $table->string('nom_unidad_empaque')->default('generico');
            $table->decimal('num_peso', 8, 2);
            $table->string('des_unidad_longitud')->default('cm');
            $table->string('des_unidad_peso')->default('kg');
            $table->string('ide_unidad_empaque')->nullable();
            $table->string('ide_envio')->nullable();
            $table->timestamp('fec_actualizacion')->nullable();
            $table->integer('num_consecutivo')->default(0);

            $table->timestamps();

            //Indices y relaciones
            $table->foreign('guia_envio_id')->references('id')->on('guias_envio')->onDelete('cascade');
            $table->index(['guia_envio_id', 'created_at']);
            
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('guia_unidades_empaque');
    }
};
