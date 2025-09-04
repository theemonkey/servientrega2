<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\TrackingServientrega;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

/**
 * ===================================================================
 * CONTROLADOR OPTIMIZADO PARA GUARDAR PNG BINARIO EN BD CON FALLBACK
 * ===================================================================
 *
 * Características:
 * - PNG binario (75% más liviano que base64)
 * - Múltiples métodos de conversión con fallbacks
 * - Verificación de extensiones disponibles
 * - Logs detallados para debugging
 * - Manejo robusto de errores sin crashes
 * - Optimización automática de imágenes
 *
 * Orden de prioridad para conversión:
 * 1. ImageMagick nativo (mejor calidad)
 * 2. Intervention Image con ImageMagick
 * 3. Intervention Image con GD
 * 4. GD nativo (fallback básico)
 */
class TrackingServientregaController extends Controller
{
    // ====== CONFIGURACIÓN DE OPTIMIZACIÓN ======
    private const IMAGE_MAX_WIDTH = 800;
    private const IMAGE_MAX_HEIGHT = 1200;
    private const IMAGE_QUALITY = 85;
    private const MAX_FILE_SIZE_MB = 5; // Máximo 5MB para procesar

    /**
     * Limpia y normaliza valores de la API
     *
     * @param mixed $valor - Valor a limpiar
     * @return string|null - Valor limpio o null
     */
    private function limpiarValor($valor)
    {
        if (is_array($valor)) {
            return implode(', ', array_filter($valor));
        }
        return $valor ?? null;
    }

    /**
     * ========================================================================
     * VERIFICACIÓN DEL SISTEMA DE PROCESAMIENTO DE IMÁGENES
     * ========================================================================
     *
     * Verifica qué extensiones están disponibles y registra el estado del sistema
     */
    private function verificarSistemaImagenes($numeroGuia)
    {
        $estado = [
            'imagick_extension' => extension_loaded('imagick'),
            'imagick_class' => class_exists('Imagick'),
            'gd_extension' => extension_loaded('gd'),
            'intervention_disponible' => class_exists('Intervention\Image\ImageManager'),
            'sistema_operativo' => php_uname('s'),
            'php_version' => phpversion()
        ];

        Log::info('🔍 VERIFICACIÓN SISTEMA DE IMÁGENES', [
            'numero_guia' => $numeroGuia,
            'estado' => $estado,
            'recomendacion' => $this->obtenerRecomendacion($estado)
        ]);

        return $estado;
    }

    /**
     * Obtiene recomendación según el estado del sistema
     */
    private function obtenerRecomendacion($estado)
    {
        if ($estado['imagick_extension'] && $estado['imagick_class']) {
            return 'Sistema óptimo: ImageMagick disponible';
        } elseif ($estado['gd_extension']) {
            return 'Sistema básico: Solo GD disponible, considerar instalar ImageMagick';
        } else {
            return 'Sistema limitado: Sin extensiones de procesamiento de imágenes';
        }
    }

    /**
     * ========================================================================
     * CONVERSIÓN TIFF A PNG BINARIO CON MÚLTIPLES FALLBACKS
     * ========================================================================
     *
     * Convierte imagen TIFF (base64) a PNG binario con sistema de fallbacks robusto
     *
     * @param string|null $tiffBase64 - Imagen TIFF en formato base64
     * @param string $numeroGuia - Número de guía para logs
     * @return string|null - PNG binario o null si falla completamente
     */
    private function convertirTiffAPngBinario($tiffBase64, $numeroGuia)
    {
        // ===== VERIFICACIONES INICIALES =====
        if (empty($tiffBase64)) {
            Log::warning("🖼️ SIN IMAGEN TIFF - Guía: {$numeroGuia}", [
                'numero_guia' => $numeroGuia,
                'razon' => 'imagen_tiff_vacia'
            ]);
            return null;
        }

        // Verificar tamaño antes de procesar
        $tamanoMB = strlen($tiffBase64) / 1024 / 1024;
        if ($tamanoMB > self::MAX_FILE_SIZE_MB) {
            Log::warning("📏 IMAGEN DEMASIADO GRANDE - Guía: {$numeroGuia}", [
                'numero_guia' => $numeroGuia,
                'tamaño_mb' => round($tamanoMB, 2),
                'maximo_mb' => self::MAX_FILE_SIZE_MB
            ]);
            return null;
        }

        // Verificar estado del sistema
        $sistemaEstado = $this->verificarSistemaImagenes($numeroGuia);

        try {
            Log::info('🔄 === INICIANDO CONVERSIÓN TIFF → PNG BINARIO ===', [
                'numero_guia' => $numeroGuia,
                'tamaño_tiff_base64_kb' => round(strlen($tiffBase64) / 1024, 2),
                'tamaño_mb' => round($tamanoMB, 2)
            ]);

            // ===== DECODIFICAR BASE64 =====
            $base64Limpio = preg_replace('/[^A-Za-z0-9+\/=]/', '', $tiffBase64);
            $tiffBinario = base64_decode($base64Limpio, true);

            if ($tiffBinario === false) {
                Log::error("❌ BASE64 INVÁLIDO - Guía: {$numeroGuia}");
                return null;
            }

            // Verificar que es TIFF válido
            if (!$this->esTiffValido($tiffBinario)) {
                Log::warning("⚠️ FORMATO NO ES TIFF VÁLIDO - Guía: {$numeroGuia}", [
                    'header_hex' => bin2hex(substr($tiffBinario, 0, 10))
                ]);
                // Continuar el procesamiento - podría ser otro formato soportado
            }

            $pngBinario = null;
            $metodoUsado = '';
            $intentos = [];

            // ===== MÉTODO 1: IMAGICK NATIVO (PRIORIDAD ALTA) =====
            if ($sistemaEstado['imagick_extension'] && $sistemaEstado['imagick_class']) {
                $pngBinario = $this->convertirConImageMagick($tiffBinario, $numeroGuia, $intentos);
                if ($pngBinario) $metodoUsado = 'ImageMagick Nativo';
            }

            // ===== MÉTODO 2: INTERVENTION IMAGE CON IMAGICK =====
            if (!$pngBinario && $sistemaEstado['intervention_disponible'] && $sistemaEstado['imagick_extension']) {
                $pngBinario = $this->convertirConInterventionImage($tiffBinario, $numeroGuia, true, $intentos);
                if ($pngBinario) $metodoUsado = 'Intervention Image (ImageMagick)';
            }

            // ===== MÉTODO 3: INTERVENTION IMAGE CON GD =====
            if (!$pngBinario && $sistemaEstado['intervention_disponible'] && $sistemaEstado['gd_extension']) {
                $pngBinario = $this->convertirConInterventionImage($tiffBinario, $numeroGuia, false, $intentos);
                if ($pngBinario) $metodoUsado = 'Intervention Image (GD)';
            }

            // ===== MÉTODO 4: GD NATIVO (FALLBACK BÁSICO) =====
            if (!$pngBinario && $sistemaEstado['gd_extension']) {
                $pngBinario = $this->convertirConGD($tiffBinario, $numeroGuia, $intentos);
                if ($pngBinario) $metodoUsado = 'GD Nativo';
            }

            // ===== RESULTADO FINAL =====
            if (!$pngBinario) {
                Log::error("❌ CONVERSIÓN FALLÓ COMPLETAMENTE - Guía: {$numeroGuia}", [
                    'numero_guia' => $numeroGuia,
                    'intentos_realizados' => $intentos,
                    'sistema_estado' => $sistemaEstado,
                    'recomendacion' => 'Verificar instalación de ImageMagick o GD'
                ]);
                return null;
            }

            // Verificar que es PNG válido
            $isPngValid = $this->esPngValido($pngBinario);

            Log::info('✅ CONVERSIÓN EXITOSA', [
                'numero_guia' => $numeroGuia,
                'metodo_usado' => $metodoUsado,
                'tamaño_tiff_kb' => round(strlen($tiffBinario) / 1024, 2),
                'tamaño_png_binario_kb' => round(strlen($pngBinario) / 1024, 2),
                'tamaño_que_sería_base64_kb' => round(strlen(base64_encode($pngBinario)) / 1024, 2),
                'ahorro_vs_base64_pct' => round((1 - strlen($pngBinario) / strlen(base64_encode($pngBinario))) * 100, 2),
                'png_válido' => $isPngValid,
                'intentos_previos' => count($intentos)
            ]);

            return $pngBinario;
        } catch (\Exception $e) {
            Log::error("💥 ERROR CRÍTICO EN CONVERSIÓN - Guía: {$numeroGuia}", [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * ========================================================================
     * MÉTODOS DE CONVERSIÓN ESPECÍFICOS
     * ========================================================================
     */

    /**
     * Conversión usando ImageMagick nativo
     */
    private function convertirConImageMagick($tiffBinario, $numeroGuia, &$intentos)
    {
        try {
            $intentos[] = 'ImageMagick Nativo';

            $imagick = new \Imagick();
            $imagick->readImageBlob($tiffBinario);

            Log::info('📐 IMAGEN CARGADA CON IMAGICK', [
                'numero_guia' => $numeroGuia,
                'ancho_original' => $imagick->getImageWidth(),
                'alto_original' => $imagick->getImageHeight(),
                'formato_original' => $imagick->getImageFormat()
            ]);

            // Optimizar tamaño si es necesario
            $this->optimizarTamanoImageMagick($imagick, $numeroGuia);

            // Configurar PNG optimizado
            $imagick->setImageFormat('png');
            $imagick->setImageCompressionQuality(self::IMAGE_QUALITY);
            $imagick->stripImage(); // Remover metadatos para ahorrar espacio

            // Obtener PNG binario
            $pngBinario = $imagick->getImageBlob();
            $imagick->clear();
            $imagick->destroy();

            return $pngBinario;
        } catch (\ImagickException $e) {
            Log::warning("⚠️ IMAGICK FALLÓ - Guía: {$numeroGuia}", [
                'error' => $e->getMessage(),
                'codigo' => $e->getCode()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::warning("⚠️ ERROR GENERAL IMAGICK - Guía: {$numeroGuia}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Conversión usando Intervention Image
     */
    private function convertirConInterventionImage($tiffBinario, $numeroGuia, $useImagick, &$intentos)
    {
        try {
            $driver = $useImagick ? 'ImageMagick' : 'GD';
            $intentos[] = "Intervention Image ({$driver})";

            $manager = $useImagick
                ? new ImageManager(new ImagickDriver())
                : new ImageManager(new GdDriver());

            $image = $manager->read($tiffBinario);

            Log::info("📐 IMAGEN CARGADA CON INTERVENTION ({$driver})", [
                'numero_guia' => $numeroGuia,
                'ancho_original' => $image->width(),
                'alto_original' => $image->height()
            ]);

            // Optimizar tamaño si es necesario
            $this->optimizarTamanoIntervention($image, $numeroGuia);

            // Convertir a PNG binario
            $pngBinario = $image->toPng(self::IMAGE_QUALITY)->toString();

            return $pngBinario;
        } catch (\Exception $e) {
            $driver = $useImagick ? 'ImageMagick' : 'GD';
            Log::warning("⚠️ INTERVENTION IMAGE ({$driver}) FALLÓ - Guía: {$numeroGuia}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Conversión usando GD nativo
     */
    private function convertirConGD($tiffBinario, $numeroGuia, &$intentos)
    {
        try {
            $intentos[] = 'GD Nativo';

            $image = imagecreatefromstring($tiffBinario);

            if ($image === false) {
                Log::warning("⚠️ GD NO PUDO CREAR IMAGEN - Guía: {$numeroGuia}");
                return null;
            }

            $width = imagesx($image);
            $height = imagesy($image);

            Log::info("📐 IMAGEN CARGADA CON GD", [
                'numero_guia' => $numeroGuia,
                'ancho_original' => $width,
                'alto_original' => $height
            ]);

            // Optimizar tamaño si es necesario
            $image = $this->optimizarTamanoGD($image, $width, $height, $numeroGuia);

            // Convertir a PNG binario
            ob_start();
            imagepng($image, null, 8); // Nivel de compresión PNG (0-9)
            $pngBinario = ob_get_contents();
            ob_end_clean();
            imagedestroy($image);

            return $pngBinario;
        } catch (\Exception $e) {
            Log::warning("⚠️ GD NATIVO FALLÓ - Guía: {$numeroGuia}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ========================================================================
     * MÉTODOS DE OPTIMIZACIÓN DE TAMAÑO
     * ========================================================================
     */

    private function optimizarTamanoImageMagick($imagick, $numeroGuia)
    {
        if (
            $imagick->getImageWidth() > self::IMAGE_MAX_WIDTH ||
            $imagick->getImageHeight() > self::IMAGE_MAX_HEIGHT
        ) {
            $imagick->resizeImage(
                self::IMAGE_MAX_WIDTH,
                self::IMAGE_MAX_HEIGHT,
                \Imagick::FILTER_LANCZOS,
                1,
                true // Mantener aspecto
            );

            Log::info('🔄 IMAGEN REDIMENSIONADA (IMAGICK)', [
                'numero_guia' => $numeroGuia,
                'nuevo_ancho' => $imagick->getImageWidth(),
                'nuevo_alto' => $imagick->getImageHeight()
            ]);
        }
    }

    private function optimizarTamanoIntervention($image, $numeroGuia)
    {
        if (
            $image->width() > self::IMAGE_MAX_WIDTH ||
            $image->height() > self::IMAGE_MAX_HEIGHT
        ) {
            $image->resize(self::IMAGE_MAX_WIDTH, self::IMAGE_MAX_HEIGHT, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            Log::info('🔄 IMAGEN REDIMENSIONADA (INTERVENTION)', [
                'numero_guia' => $numeroGuia,
                'nuevo_ancho' => $image->width(),
                'nuevo_alto' => $image->height()
            ]);
        }
    }

    private function optimizarTamanoGD($image, $width, $height, $numeroGuia)
    {
        if ($width > self::IMAGE_MAX_WIDTH || $height > self::IMAGE_MAX_HEIGHT) {
            $ratio = min(self::IMAGE_MAX_WIDTH / $width, self::IMAGE_MAX_HEIGHT / $height);
            $newWidth = intval($width * $ratio);
            $newHeight = intval($height * $ratio);

            $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($optimizedImage, false);
            imagesavealpha($optimizedImage, true);

            imagecopyresampled(
                $optimizedImage,
                $image,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $width,
                $height
            );

            imagedestroy($image);

            Log::info('🔄 IMAGEN REDIMENSIONADA (GD)', [
                'numero_guia' => $numeroGuia,
                'nuevo_ancho' => $newWidth,
                'nuevo_alto' => $newHeight
            ]);

            return $optimizedImage;
        }

        return $image;
    }

    /**
     * ========================================================================
     * MÉTODOS DE VALIDACIÓN
     * ========================================================================
     */

    /**
     * Verifica si los datos binarios corresponden a un archivo TIFF válido
     */
    private function esTiffValido($binario)
    {
        if (strlen($binario) < 4) return false;

        $header = substr($binario, 0, 4);
        return $header === "MM\x00\x2A" || $header === "II\x2A\x00";
    }

    /**
     * Verifica si los datos binarios corresponden a un archivo PNG válido
     */
    private function esPngValido($binario)
    {
        if (strlen($binario) < 8) return false;

        return substr($binario, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";
    }

    /**
     * ========================================================================
     * PROCESAMIENTO PRINCIPAL DE GUÍAS
     * ========================================================================
     *
     * Procesa guía con PNG binario optimizado y manejo robusto de errores
     */
    private function procesarGuia($numeroGuia, $logAcceso = false)
    {
        // Validar formato de guía
        if (!preg_match('/^[0-9]+$/', $numeroGuia)) {
            throw new \Exception('Formato de guía inválido: debe contener solo números');
        }

        if ($logAcceso) {
            Log::info('🔍 ACCESO DIRECTO A GUÍA', [
                'numero_guia' => $numeroGuia,
                'timestamp' => now()->toDateTimeString(),
                'usuario' => 'Will-AGW'
            ]);
        }

        // ===== CONSULTAR API =====
        try {
            $response = Http::timeout(60)->get(
                "https://wssismilenio.servientrega.com/wsrastreoenvios/wsrastreoenvios.asmx/ConsultarGuiaExterno",
                ['NumeroGuia' => $numeroGuia]
            );

            if (!$response->successful()) {
                throw new \Exception("Guía {$numeroGuia} no encontrada en el sistema de Servientrega");
            }

            Log::info('📡 API CONSULTADA EXITOSAMENTE', [
                'numero_guia' => $numeroGuia,
                'codigo_respuesta' => $response->status(),
                'tamaño_respuesta_kb' => round(strlen($response->body()) / 1024, 2)
            ]);
        } catch (\Exception $e) {
            Log::error('❌ ERROR CONSULTANDO API', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage(),
                'tipo' => get_class($e)
            ]);
            throw $e;
        }

        // ===== PROCESAR XML =====
        try {
            $xml = simplexml_load_string($response->body());
            if ($xml === false) {
                throw new \Exception('Respuesta XML inválida del servicio');
            }
            $array = json_decode(json_encode($xml), true);

            Log::info('🔄 XML PROCESADO CORRECTAMENTE', [
                'numero_guia' => $numeroGuia,
                'campos_principales' => array_keys($array)
            ]);
        } catch (\Exception $e) {
            Log::error('❌ ERROR PROCESANDO XML', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Error procesando respuesta del servicio de Servientrega');
        }

        // ===== NORMALIZAR MOVIMIENTOS =====
        $movimientos = $array['Mov']['InformacionMov'] ?? [];
        if (!is_array($movimientos)) {
            $movimientos = [$movimientos];
        }

        Log::info('📋 MOVIMIENTOS PROCESADOS', [
            'numero_guia' => $numeroGuia,
            'cantidad_movimientos' => count($movimientos)
        ]);

        // ===== PROCESAMIENTO DE IMAGEN CON FALLBACKS =====
        $imagenPngBinario = null;
        $estadoImagen = 'sin_imagen';

        if (isset($array['Imagen']) && !empty($array['Imagen'])) {
            $imagenTiffOriginal = is_array($array['Imagen'])
                ? $array['Imagen'][0]
                : $array['Imagen'];

            Log::info('🖼️ IMAGEN DETECTADA - INICIANDO PROCESAMIENTO', [
                'numero_guia' => $numeroGuia,
                'tamaño_tiff_original_kb' => round(strlen($imagenTiffOriginal) / 1024, 2)
            ]);

            // Convertir con sistema de fallbacks
            $imagenPngBinario = $this->convertirTiffAPngBinario($imagenTiffOriginal, $numeroGuia);

            if ($imagenPngBinario) {
                $estadoImagen = 'convertida_exitosa';
                Log::info('✅ IMAGEN PROCESADA Y OPTIMIZADA', [
                    'numero_guia' => $numeroGuia,
                    'tamaño_final_kb' => round(strlen($imagenPngBinario) / 1024, 2)
                ]);
            } else {
                $estadoImagen = 'error_conversion';
                Log::warning('⚠️ NO SE PUDO PROCESAR LA IMAGEN', [
                    'numero_guia' => $numeroGuia,
                    'razon' => 'Todos los métodos de conversión fallaron',
                    'recomendacion' => 'Verificar instalación de ImageMagick o GD en el servidor'
                ]);
            }
        } else {
            Log::info('ℹ️ SIN IMAGEN EN RESPUESTA', ['numero_guia' => $numeroGuia]);
        }

        // ===== PREPARAR DATOS PARA BD =====
        $identificador = ['numero_guia' => $numeroGuia];
        $datos = [
            'fec_env' => $this->limpiarValor($array['FecEnv'] ?? null),
            'num_pie' => $this->limpiarValor($array['NumPie'] ?? null),
            'ciu_remitente' => $this->limpiarValor($array['CiuRem'] ?? null),
            'nom_remitente' => $this->limpiarValor($array['NomRem'] ?? null),
            'dir_remitente' => $this->limpiarValor($array['DirRem'] ?? null),
            'ciu_destinatario' => $this->limpiarValor($array['CiuDes'] ?? null),
            'nom_destinatario' => $this->limpiarValor($array['NomDes'] ?? null),
            'dir_destinatario' => $this->limpiarValor($array['DirDes'] ?? null),
            'id_estado_actual' => $this->limpiarValor($array['IdEstAct'] ?? null),
            'estado_actual' => $this->limpiarValor($array['EstAct'] ?? null),
            'fecha_estado' => $this->limpiarValor($array['FecEst'] ?? null),
            'nom_receptor' => $this->limpiarValor($array['NomRec'] ?? null),
            'num_cun' => $this->limpiarValor($array['NumCun'] ?? null),
            'regimen' => $this->limpiarValor($array['Regime'] ?? null),
            'placa' => $this->limpiarValor($array['Placa'] ?? null),
            'id_gps' => $this->limpiarValor($array['IdGPS'] ?? null),
            'forma_pago' => $this->limpiarValor($array['FormPago'] ?? null),
            'nomb_producto' => $this->limpiarValor($array['NomProducto'] ?? null),
            'fecha_probable' => $this->limpiarValor($array['FechaProbable'] ?? null),
            'movimientos' => $movimientos,

            // 🎯 CLAVE: PNG binario (75% más liviano que base64)
            'imagen_png_binario' => $imagenPngBinario,
        ];

        // ===== ALMACENAR EN BD =====
        try {
            $trackingRecord = TrackingServientrega::updateOrCreate($identificador, $datos);

            Log::info('💾 GUÍA ALMACENADA CON OPTIMIZACIÓN', [
                'numero_guia' => $numeroGuia,
                'id_registro' => $trackingRecord->id,
                'estado_imagen' => $estadoImagen,
                'imagen_optimizada' => !empty($imagenPngBinario),
                'tamaño_imagen_kb' => !empty($imagenPngBinario) ? round(strlen($imagenPngBinario) / 1024, 2) : 0,
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('❌ ERROR ALMACENANDO EN BD', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]);
            throw new \Exception('Error almacenando información de la guía en la base de datos');
        }

        return [
            'array' => $array,
            'trackingRecord' => $trackingRecord,
            'estadoImagen' => $estadoImagen
        ];
    }

    /**
     * ========================================================================
     * MÉTODOS PÚBLICOS - ENDPOINTS
     * ========================================================================
     */

    /**
     * Consulta guía desde formulario
     */
    public function consultarGuia(Request $request)
    {
        $request->validate([
            'numero_guia' => 'required|numeric'
        ], [
            'numero_guia.required' => 'El número de guía es obligatorio',
            'numero_guia.numeric' => 'El número de guía debe contener solo números'
        ]);

        $numeroGuia = $request->input('numero_guia');

        try {
            $resultado = $this->procesarGuia($numeroGuia);

            return view('resultados', [
                'respuesta' => $resultado['array'],
                'trackingRecord' => $resultado['trackingRecord'],
                'numeroGuia' => $numeroGuia,
                'estadoImagen' => $resultado['estadoImagen'] ?? 'desconocido'
            ]);
        } catch (\Exception $e) {
            Log::error('❌ ERROR EN CONSULTA DESDE FORMULARIO', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage(),
                'usuario' => 'Will-AGW'
            ]);

            return back()
                ->withInput()
                ->withErrors(['Error al consultar la guía: ' . $e->getMessage()]);
        }
    }

    /**
     * Ver guía desde URL directa
     */
    public function verGuia($numeroGuia, Request $request)
    {
        try {
            $resultado = $this->procesarGuia($numeroGuia, true);
            $origen = $request->get('origen', $request->header('referer', '/'));

            return view('guia-detalle', [
                'respuesta' => $resultado['array'],
                'trackingRecord' => $resultado['trackingRecord'],
                'numeroGuia' => $numeroGuia,
                'urlOrigen' => $origen,
                'estadoImagen' => $resultado['estadoImagen'] ?? 'desconocido'
            ]);
        } catch (\Exception $e) {
            Log::error('❌ ERROR EN CONSULTA DIRECTA', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage(),
                'url_origen' => $request->get('origen'),
                'usuario' => 'Will-AGW'
            ]);

            return view('guia-detalle-error', [
                'mensaje' => $e->getMessage(),
                'numeroGuia' => $numeroGuia,
                'urlOrigen' => $request->get('origen', $request->header('referer', '/'))
            ]);
        }
    }

    /**
     * ========================================================================
     * ENDPOINT PARA DIAGNÓSTICO DEL SISTEMA (OPCIONAL)
     * ========================================================================
     *
     * Endpoint para verificar el estado del sistema de procesamiento de imágenes
     * Útil para debugging en producción
     */
    public function diagnosticoSistema()
    {
        $diagnostico = [
            'timestamp' => now()->toDateTimeString(),
            'servidor' => [
                'hostname' => gethostname(),
                'php_version' => phpversion(),
                'sistema_operativo' => php_uname(),
                'memoria_limite' => ini_get('memory_limit'),
                'tiempo_ejecucion_max' => ini_get('max_execution_time')
            ],
            'extensiones' => [
                'imagick' => [
                    'extension_cargada' => extension_loaded('imagick'),
                    'clase_disponible' => class_exists('Imagick'),
                    'version' => extension_loaded('imagick') ? \Imagick::getVersion()['versionString'] ?? 'No disponible' : 'No instalada'
                ],
                'gd' => [
                    'extension_cargada' => extension_loaded('gd'),
                    'version' => extension_loaded('gd') ? gd_info()['GD Version'] ?? 'No disponible' : 'No instalada',
                    'formatos_soportados' => extension_loaded('gd') ? array_keys(gd_info()) : []
                ],
                'intervention_image' => [
                    'disponible' => class_exists('Intervention\Image\ImageManager'),
                    'version' => class_exists('Intervention\Image\ImageManager') ? 'Instalado' : 'No disponible'
                ]
            ],
            'configuracion' => [
                'imagen_max_ancho' => self::IMAGE_MAX_WIDTH,
                'imagen_max_alto' => self::IMAGE_MAX_HEIGHT,
                'calidad_imagen' => self::IMAGE_QUALITY,
                'tamaño_max_archivo_mb' => self::MAX_FILE_SIZE_MB
            ],
            'recomendaciones' => $this->obtenerRecomendacionesCompletas()
        ];

        return response()->json($diagnostico, 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Obtiene recomendaciones completas del sistema
     */
    private function obtenerRecomendacionesCompletas()
    {
        $recomendaciones = [];

        if (!extension_loaded('imagick')) {
            $recomendaciones[] = '⚠️  Instalar ImageMagick para mejor calidad de conversión: sudo apt-get install php-imagick';
        }

        if (!extension_loaded('gd')) {
            $recomendaciones[] = '⚠️  Instalar GD como fallback básico: sudo apt-get install php-gd';
        }

        if (!class_exists('Intervention\Image\ImageManager')) {
            $recomendaciones[] = '💡 Considerar instalar Intervention Image: composer require intervention/image';
        }

        if (empty($recomendaciones)) {
            $recomendaciones[] = '✅ Sistema óptimo para procesamiento de imágenes';
        }

        return $recomendaciones;
    }
}
