<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoGuia extends Model
{
    protected $table = 'movimientos_guia';

    protected $fillable = [
        'guia_id',
        'estado_movimiento',
        'descripcion_movimiento',
        'fecha_movimiento',
        'ciudad_movimiento',
    ];

    protected $cast = [
        'fecha_movimiento' => 'datetime',
    ];

    public function guia(): BelongsTo {return $this->belongsTo(Guia::class);}
}

