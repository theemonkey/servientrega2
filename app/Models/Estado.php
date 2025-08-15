<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    protected $table = 'estados';

    protected $fillable = [
        'codigo_estado',
        'nombre_estado',
        'descripcion',
    ];

    //opcional, si lanza error eliminar
    public function guias(){
        return $this->hasMany(Guia::class, 'estado_actual_id');
    }
}
