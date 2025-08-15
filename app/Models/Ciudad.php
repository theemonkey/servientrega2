<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ciudad extends Model
{
    protected $table = 'ciudades';

    protected $fillable = [
        'codigo_dane',
        'nombre_ciudad',
        'nombre_departamento',
        'tipo_distribucion',
        'codigo_oficina',
    ];

//opcional, si lanza error eliminar
    public function guiasRemitidas(){
        return $this->hasMany(Guia::class, 'ciudad_remitente_id');
    }

    public function guiasRecibidas(){
        return $this->hasMany(Guia::class, 'ciudad_destino_id');
    }
}
