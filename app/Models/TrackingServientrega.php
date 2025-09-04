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
        'imagen_png_binario',
        'movimientos',
    ];

    protected $casts = [
        'movimientos' => 'array',
        'fec_env' => 'datetime',
        'fecha_estado' => 'datetime',
        'fecha_probable' => 'datetime',
    ];

    /**
     * Verifica si tiene imagen PNG
     */
    public function tieneImagen()
    {
        return !empty($this->imagen_png_binario) &&
            substr($this->imagen_png_binario, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";
    }

    /**
     * Convierte PNG binario a base64 para mostrar en vista
     */
    public function getImagenBase64ParaVistaAttribute()
    {
        if (!$this->tieneImagen()) {
            return null;
        }

        return base64_encode($this->imagen_png_binario);
    }
}
