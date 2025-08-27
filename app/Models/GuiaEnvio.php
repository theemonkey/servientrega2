<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuiaEnvio extends Model
{
    use HasFactory;

    protected $table = 'guias_envio';

    protected $fillable = [
        'user_id',
        'num_guia',
        'num_sobreporte',
        'num_sobre_caja_porte',
        'num_piezas',
        'des_tipo_trayecto',
        'ide_producto',
        'ide_destinatarios',
        'ide_manifiesto',
        'des_forma_pago',
        'des_medio_transporte',
        'num_peso_total',
        'num_valor_declarado_total',
        'num_volumen_total',
        'num_bolsa_seguridad',
        'num_precinto',
        'des_tipo_duracion_trayecto',
        'des_telefono',
        'des_ciudad',
        'des_direccion',
        'nom_contacto',
        'des_vlr_campo_personalizado1',
        'num_valor_liquidado',
        'des_dice_contener',
        'des_tipo_guia',
        'num_vlr_sobreflete',
        'num_vlr_flete',
        'num_descuento',
        'ide_pais_origen',
        'ide_pais_destino',
        'des_id_archivo_origen',
        'des_direccion_remitente',
        'num_peso_facturado',
        'est_canal_mayorista',
        'num_identi_remitente',
        'num_telefono_remitente',
        'num_alto',
        'num_ancho',
        'num_largo',
        'des_departamento_destino',
        'gen_cajaporte',
        'gen_sobreporte',
        'nom_unidad_empaque',
        'des_correo_electronico',
        'id_archivo_cargar',
        'est_enviar_correo',
        'retorno_digital',
        'estado',
        'mensaje_error',
        'respuesta_completa_api',
        'fecha_generacion',
        'referencia_cliente',
        'observaciones'
    ];

    protected $casts = [
        'num_peso_total' => 'decimal:2',
        'num_valor_declarado_total' => 'decimal:2',
        'num_volumen_total' => 'decimal:2',
        'num_valor_liquidado' => 'decimal:2',
        'num_vlr_sobreflete' => 'decimal:2',
        'num_vlr_flete' => 'decimal:2',
        'num_descuento' => 'decimal:2',
        'num_peso_facturado' => 'decimal:2',
        'num_alto' => 'decimal:2',
        'num_ancho' => 'decimal:2',
        'num_largo' => 'decimal:2',
        'est_canal_mayorista' => 'boolean',
        'gen_cajaporte' => 'boolean',
        'gen_sobreporte' => 'boolean',
        'est_enviar_correo' => 'boolean',
        'respuesta_completa_api' => 'array',
        'fecha_generacion' => 'datetime'
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unidadesEmpaque(): HasMany
    {
        return $this->hasMany(GuiaUnidadEmpaque::class);
    }

    // Scopes
    public function scopeGeneradas($query)
    {
        return $query->where('estado', 'generada');
    }

    public function scopeDelUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Accessors
    public function getEstadoFormateadoAttribute()
    {
        $estados = [
            'borrador' => 'Borrador',
            'procesando' => 'Procesando',
            'generada' => 'Generada',
            'error' => 'Error'
        ];

        return $estados[$this->estado] ?? $this->estado;
    }

    public function getCostoTotalAttribute()
    {
        return $this->num_vlr_flete + $this->num_vlr_sobreflete - $this->num_descuento;
    }

    // Métodos de negocio
    public function marcarComoGenerada($numeroGuia, $respuestaApi)
    {
        $this->update([
            'num_guia' => $numeroGuia,
            'estado' => 'generada',
            'fecha_generacion' => now(),
            'respuesta_completa_api' => $respuestaApi
        ]);
    }

    public function marcarComoError($mensajeError)
    {
        $this->update([
            'estado' => 'error',
            'mensaje_error' => $mensajeError
        ]);
    }
}