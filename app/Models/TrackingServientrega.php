<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class TrackingServientrega extends Model
{
    use HasFactory;

    protected $table = 'tracking_servientrega';

    protected $fillable = [
        'numero_guia',
        'fec_env',
        'num_pie',
        'ciu_remitente',
        'nom_remitente',
        'dir_remitente',
        'ciu_destinatario',
        'nom_destinatario',
        'dir_destinatario',
        'id_estado_actual',
        'estado_actual',
        'fecha_estado',
        'nom_receptor',
        'num_cun',
        'regimen',
        'placa',
        'id_gps',
        'forma_pago',
        'nomb_producto',
        'fecha_probable',
        'imagen_base64',
        'movimientos',
    ];

    protected $casts = [
        'movimientos' => 'array',
        'fec_env' => 'datetime',
        'fecha_estado' => 'datetime',
        'fecha_probable' => 'datetime',
    ];
}