<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'guia_id',
        'tipo_servicio',
        'tipo_empaque',
        'peso_fisico',
        'peso_volumen',
        'largo',
        'ancho',
        'alto',
        'valor_declarado',
        'costo_flete',
        'valor_sobretasa',
        'valor_total',
        'origen_id',
        'destino_id',
    ];

    public function guia(): BelongsTo { return $this->belongsTo(Guia::class); }
    public function ciudadOrigen(): BelongsTo { return $this->belongsTo(Ciudad::class, 'origen_id'); }
    public function ciudadDestino(): BelongsTo { return $this->belongsTo(Ciudad::class, 'destino_id'); }
}
