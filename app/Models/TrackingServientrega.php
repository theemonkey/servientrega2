<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;


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

    /**
     * Verifica si tiene imagen(busca archivo fisico)
     */
    public function tieneImagen()
    {
        if (empty($this->imagen_base64)) {
            return false;
        }
        //verificar que el archivo existe fisicamente
        $rutaCompleta = public_path($this->imagen_base64);
        return File::exists($rutaCompleta);
        //return !empty($this->imagen_base64);
    }

    /**
     * Obtiene la URL pública de la imagen
     */
    public function getUrlImagen()
    {
        if (!$this->tieneImagen()) {
            return null;
        }

        return asset($this->imagen_base64);
    }

    /**
     * Obtiene la ruta completa del archivo
     */
    public function getRutaCompletaImagen()
    {
        if (empty($this->imagen_base64)) {
            return null;
        }

        return public_path($this->imagen_base64);
    }

    /**
     * Obtiene imagen como base64 para descarga directa (SIN data: prefix)
     */
    public function getImagenBase64ParaDescarga()
    {
        if (!$this->tieneImagen()) {
            return null;
        }

        try {
            $rutaCompleta = $this->getRutaCompletaImagen();
            $contenidoArchivo = File::get($rutaCompleta);
            return base64_encode($contenidoArchivo);
        } catch (\Exception $e) {
            Log::error(' Error leyendo imagen para descarga base64', [
                'numero_guia' => $this->numero_guia,
                'ruta_imagen' => $this->imagen_base64,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Obtiene imagen como base64 para vista
     */
    public function getImagenBase64ParaVistaAttribute()
    {
        $base64 = $this->getImagenBase64ParaDescarga();
        if (!$base64) {
            return null;
        }
        // Detectar tipo MIME basado en extension de archivo
        $extension = pathinfo($this->imagen_base64, PATHINFO_EXTENSION);
        $mimeType = $this->getMimeTypePorExtension($extension);

        return "data:{$mimeType};base64,{$base64}";
    }

    /**
     * Obtiene contenido binario para descarga directa de archivo
     */
    public function getContenidoImagenParaDescarga()
    {
        if (!$this->tieneImagen()) {
            return null;
        }

        try {
            $rutaCompleta = $this->getRutaCompletaImagen();
            return File::get($rutaCompleta);
        } catch (\Exception $e) {
            Log::error(' Error leyendo contenido binario', [
                'numero_guia' => $this->numero_guia,
                'ruta_imagen' => $this->imagen_base64,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * =====================================
     * MÉTODOS DE INFORMACIÓN
     * =====================================
     */

    /**
     * Obtiene información completa del archivo de imagen
     */
    public function getInfoImagen()
    {
        if (!$this->tieneImagen()) {
            return null;
        }

        try {
            $rutaCompleta = $this->getRutaCompletaImagen();
            $tamaño = File::size($rutaCompleta);
            $extension = pathinfo($this->imagen_base64, PATHINFO_EXTENSION);

            // Intentar obtener dimensiones si es posible
            $dimensiones = $this->obtenerDimensionesImagen($rutaCompleta);

            return [
                'ruta_relativa' => $this->imagen_base64,
                'ruta_completa' => $rutaCompleta,
                'url' => $this->getUrlImagen(),
                'nombre_archivo' => basename($this->imagen_base64),
                'extension' => $extension,
                'ancho' => $dimensiones['ancho'] ?? null,
                'alto' => $dimensiones['alto'] ?? null,
                'tipo_mime' => $this->getMimeTypePorExtension($extension),
                'tamaño_bytes' => $tamaño,
                'tamaño_kb' => round($tamaño / 1024, 2),
                'tamaño_mb' => round($tamaño / (1024 * 1024), 2),
            ];
        } catch (\Exception $e) {
            Log::error(' Error obteniendo información de imagen', [
                'numero_guia' => $this->numero_guia,
                'ruta_imagen' => $this->imagen_base64,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * =====================================
     * MÉTODOS AUXILIARES PRIVADOS
     * =====================================
     */

    /**
     * Mapea extensiones de archivo a tipos MIME
     */
    private function getMimeTypePorExtension($extension)
    {
        $mimeMap = [
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf'
        ];

        return $mimeMap[strtolower($extension)] ?? 'application/octet-stream';
    }

    /**
     * Intenta obtener dimensiones de imagen (solo si getimagesize funciona)
     */
    private function obtenerDimensionesImagen($rutaCompleta)
    {
        try {
            $info = getimagesize($rutaCompleta);
            return [
                'ancho' => $info[0] ?? null,
                'alto' => $info[1] ?? null
            ];
        } catch (\Exception $e) {
            // getimagesize no funciona con TIFF en muchos casos, esto es normal
            return ['ancho' => null, 'alto' => null];
        }
    }

    /**
     * Verifica estado completo de imagen
     */
    public function verificarEstadoImagen()
    {
        $resultado = [
            'tiene_ruta_db' => !empty($this->imagen_base64),
            'ruta_relativa' => $this->imagen_base64,
            'archivo_existe' => false,
            'es_legible' => false,
            'tamaño' => 0,
            'mensaje' => 'Sin imagen'
        ];

        if (empty($this->imagen_base64)) {
            return $resultado;
        }

        $rutaCompleta = public_path($this->imagen_base64);
        $resultado['ruta_completa'] = $rutaCompleta;
        $resultado['archivo_existe'] = File::exists($rutaCompleta);

        if ($resultado['archivo_existe']) {
            $resultado['es_legible'] = File::isReadable($rutaCompleta);
            $resultado['tamaño'] = File::size($rutaCompleta);
            $resultado['mensaje'] = 'Imagen disponible';
        } else {
            $resultado['mensaje'] = 'Archivo no encontrado';
        }

        return $resultado;
    }

    /**
     * =====================================
     * MÉTODOS DE LIMPIEZA
     * =====================================
     */

    /**
     * Elimina archivo de imagen del filesystem
     */
    public function eliminarImagenArchivo()
    {
        if (!$this->tieneImagen()) {
            return true;
        }

        try {
            $rutaCompleta = $this->getRutaCompletaImagen();
            if (File::exists($rutaCompleta)) {
                File::delete($rutaCompleta);
                Log::info(' Imagen eliminada del filesystem', [
                    'numero_guia' => $this->numero_guia,
                    'ruta_eliminada' => $rutaCompleta
                ]);
            }
            return true;
        } catch (\Exception $e) {
            Log::error(' Error eliminando imagen del filesystem', [
                'numero_guia' => $this->numero_guia,
                'ruta_imagen' => $this->imagen_base64,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Hook del modelo: eliminar archivo al eliminar registro
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($trackingRecord) {
            $trackingRecord->eliminarImagenArchivo();
        });
    }

    /**
     * =====================================
     * MÉTODOS DE COMPATIBILIDAD (DEPRECATED)
     * =====================================
     */

    /**
     * @deprecated Usar getImagenBase64ParaDescarga() en su lugar
     */
    private function esPngBinario()
    {
        // Ya no se usa, pero mantenido por compatibilidad
        return false;
    }
}
