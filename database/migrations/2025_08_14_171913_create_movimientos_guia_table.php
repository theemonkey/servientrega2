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
        Schema::create('movimientos_guia', function (Blueprint $table) {
            $table->id();
            $table->string('estado_movimiento')->nullable();
            $table->string('descripcion_movimiento')->nullable();
            $table->dateTime('fecha_movimiento')->nullable();
            $table->string('ciudad_movimiento')->nullable();

            $table->foreignId('guia_id')->nullable()->constrained('guias');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_guia');
    }
};
