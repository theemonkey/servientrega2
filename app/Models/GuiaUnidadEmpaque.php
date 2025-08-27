<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuiaUnidadEmpaque extends Model
{
    use HasFactory;

    protected $table = 'guia_unidades_empaque';

    protected $fillable = [
        'guia_envio_id',
        'num_alto',
        'num_distribuidor',
        'num_ancho',
        'num_cantidad',
        'des_dice_contener',
        'des_id_archivo_origen',
        'num_largo',
        'nom_unidad_empaque',
        'num_peso',
        'des_unidad_longitud',
        'des_unidad_peso',
        'ide_unidad_empaque',
        'ide_envio',
        'fec_actualizacion',
        'num_consecutivo'
    ];

    protected $casts = [
        'num_alto' => 'decimal:2',
        'num_ancho' => 'decimal:2',
        'num_largo' => 'decimal:2',
        'num_peso' => 'decimal:2',
        'fec_actualizacion' => 'datetime'
    ];

    public function guiaEnvio(): BelongsTo
    {
        return $this->belongsTo(GuiaEnvio::class, 'guia_envio_id');
    }

    // Accessors
    public function getVolumenAttribute()
    {
        return $this->num_alto * $this->num_ancho * $this->num_largo;
    }

    public function getPesoTotalAttribute()
    {
        return $this->num_peso * $this->num_cantidad;
    }

    public function getVolumenTotalAttribute()
    {
        return $this->getVolumenAttribute() * $this->num_cantidad;
    }
}