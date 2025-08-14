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
        'estado',
        'ciudad',
        'fecha',
        'respuesta',
    ];

    protected $casts = [
        'respuesta' => 'array',
        'fecha' => 'date',
    ];
}
