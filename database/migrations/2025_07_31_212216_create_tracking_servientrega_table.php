<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        //  Crear tabla completa con SQL directo (una sola operación)
        DB::statement("
            CREATE TABLE tracking_servientrega (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                numero_guia VARCHAR(255) NULL,
                fec_env TIMESTAMP NULL,
                num_pie INT NULL,
                ciu_remitente VARCHAR(255) NULL,
                nom_remitente VARCHAR(255) NULL,
                dir_remitente VARCHAR(255) NULL,
                ciu_destinatario VARCHAR(255) NULL,
                nom_destinatario VARCHAR(255) NULL,
                dir_destinatario VARCHAR(255) NULL,
                id_estado_actual INT NULL,
                estado_actual VARCHAR(255) NULL,
                fecha_estado TIMESTAMP NULL,
                nom_receptor VARCHAR(255) NULL,
                num_cun INT NULL,
                regimen VARCHAR(255) NULL,
                placa VARCHAR(255) NULL,
                id_gps INT NULL,
                forma_pago VARCHAR(255) NULL,
                nomb_producto VARCHAR(255) NULL,
                fecha_probable TIMESTAMP NULL,
                movimientos JSON NULL,
                imagen_png_binario LONGBLOB NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS tracking_servientrega');
    }
};
