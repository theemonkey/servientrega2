<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Guia extends Model
{
    protected $table = 'guias';

    protected $fillable =[
        'numero_guia',
        'fecha_envio',
        'numero_piezas',
        'remitente_nombre',
        'remitente_direccion',
        'destinatario_nombre',
        'destinatario_direccion',
        'fecha_probable_entrega',
        'regimen',
        'estado_actual_id',
        'ciudad_remitente_id',
        'ciudad_destino_id',
    ];

     protected $casts = [
        'fecha_envio' => 'datetime',
        'fecha_probable_entrega' => 'datetime',
     ];

    public function estado(): BelongsTo { return $this->belongsTo(Estado::class, 'estado_actual_id'); }
    public function ciudadRemitente(): BelongsTo { return $this->belongsTo(Ciudad::class, 'ciudad_remitente_id'); }
    public function ciudadDestino(): BelongsTo { return $this->belongsTo(Ciudad::class, 'ciudad_destino_id'); }
    public function movimientos(): HasMany { return $this->hasMany(MovimientoGuia::class, 'guia_id'); }
    public function cotizaciones(): HasMany { return $this->hasMany(Cotizacion::class, 'guia_id'); }


}
