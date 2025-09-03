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
 * Controlador para el rastreo de guías usando api de Servientrega
 *
 * Este controlador maneja la consulta de guías de envío a través de la API
 * de Servientrega, procesa las respuestas XML, convierte imágenes TIFF a PNG
 * y almacena la información en la base de datos.
 *
 * Funcionalidades principales:
 * - Consulta de guías por número
 * - Conversión de comprobantes TIFF a PNG
 * - Almacenamiento en base de datos
 * - Manejo de errores y logging
 *
 * APIs utilizadas:
 * - Servientrega: https://wssismilenio.servientrega.com/wsrastreoenvios/wsrastreoenvios.asmx/ConsultarGuiaExterno
 *
 * Dependencias:
 * - Intervention Image (conversión de imágenes)
 * - Imagick o GD (procesamiento de imágenes)
 * - Laravel HTTP Client (peticiones API)
 */
class TrackingServientregaController extends Controller
{
    /**
     * Limpia y normaliza valores antes de guardar en base de datos
     *
     * Maneja tanto arrays como valores únicos, convirtiendo arrays
     * a strings separados por comas y manejando valores nulos.
     *
     * @param mixed $valor El valor a limpiar (string, array, o null)
     * @return string|null El valor limpio como string o null
     *
     * @example
     * limpiarValor(['Buenos Aires', 'CABA']) → 'Buenos Aires, CABA'
     * limpiarValor('Bogotá') → 'Bogotá'
     * limpiarValor(null) → null
     * limpiarValor(['', null, 'valor']) → 'valor'
     */
    private function limpiarValor($valor)
    {
        if (is_array($valor)) {
            return implode(', ', array_filter($valor));
        }
        return $valor ?? null;
    }

    /**
     * Convierte imagen TIFF en formato base64 a PNG base64
     *
     * Utiliza múltiples métodos de conversión como fallback:
     * 1. Imagick directo (mejor calidad y compatibilidad)
     * 2. Intervention Image con driver Imagick/GD
     * 3. GD nativo (limitado pero funcional)
     *
     * @param string $tiffBase64 Imagen TIFF codificada en base64
     * @return string|null PNG codificado en base64 o null si falla la conversión
     *
     * @throws \Exception Si hay errores en el procesamiento
     *
     * @example
     * $pngBase64 = $this->convertirTiffAPng($tiffFromAPI);
     * if ($pngBase64) {
     *     // Imagen convertida exitosamente
     *     $imgTag = "<img src='data:image/png;base64,{$pngBase64}' />";
     * }
     *
     * @see https://www.php.net/manual/en/book.imagick.php
     * @see https://image.intervention.io/
     *
     * Formatos soportados de entrada:
     * - TIFF (Tagged Image File Format)
     * - TIFF con compresión LZW
     * - TIFF multipage (toma la primera página)
     *
     * Formato de salida:
     * - PNG (Portable Network Graphics)
     * - Base64 encoded para almacenamiento en BD
     */
    private function convertirTiffAPng($tiffBase64)
    {
        try {
            // Sanitizar entrada: eliminar caracteres no válidos de base64
            $base64Limpio = preg_replace('/[^A-Za-z0-9+\/=]/', '', $tiffBase64);

            // Decodificar base64 a datos binarios
            $tiffBinario = base64_decode($base64Limpio, true);

            if ($tiffBinario === false) {
                Log::warning('Conversión TIFF: No se pudo decodificar base64');
                return null;
            }

            /*
             * MÉTODO 1: Imagick directo
             *
             * Imagick es la librería más robusta para TIFF, especialmente
             * para archivos con compresión compleja o múltiples páginas.
             * Ofrece mejor control sobre el proceso de conversión.
             */
            if (extension_loaded('imagick') && class_exists('Imagick')) {
                try {
                    /** @var \Imagick $imagick */
                    $imagick = new \Imagick();

                    // Leer imagen desde datos binarios
                    $imagick->readImageBlob($tiffBinario);

                    // Establecer formato de salida
                    $imagick->setImageFormat('png');

                    // Obtener datos PNG
                    $pngData = $imagick->getImageBlob();
                    $pngBase64 = base64_encode($pngData);

                    // Liberar memoria
                    $imagick->clear();
                    $imagick->destroy();

                    Log::info('Conversión TIFF: Exitosa con Imagick', [
                        'tamaño_entrada' => strlen($tiffBinario),
                        'tamaño_salida' => strlen($pngData)
                    ]);

                    return $pngBase64;
                } catch (\Exception $e) {
                    Log::warning('Conversión TIFF: Imagick falló', [
                        'error' => $e->getMessage(),
                        'codigo' => $e->getCode()
                    ]);
                }
            }

            /*
             * MÉTODO 2: Intervention Image
             *
             * Intervention Image es una librería de alto nivel que abstrae
             * tanto Imagick como GD. Ofrece una API más limpia y manejo
             * automático de drivers.
             */
            try {
                // Seleccionar driver basado en disponibilidad
                if (extension_loaded('imagick')) {
                    $manager = new ImageManager(new ImagickDriver());
                    $driverName = 'Imagick';
                } else {
                    $manager = new ImageManager(new GdDriver());
                    $driverName = 'GD';
                }

                // Leer y convertir imagen
                $image = $manager->read($tiffBinario);
                $pngData = $image->toPng()->toString();

                Log::info("Conversión TIFF: Exitosa con Intervention Image ({$driverName})", [
                    'ancho' => $image->width(),
                    'alto' => $image->height(),
                    'tamaño_salida' => strlen($pngData)
                ]);

                return base64_encode($pngData);
            } catch (\Exception $e) {
                Log::warning('Conversión TIFF: Intervention Image falló', [
                    'error' => $e->getMessage(),
                    'driver_disponible' => extension_loaded('imagick') ? 'Imagick' : 'GD'
                ]);
            }

            /*
             * MÉTODO 3: GD nativo (fallback)
             *
             * GD tiene soporte limitado para TIFF pero puede manejar
             * archivos simples sin compresión. Es el último recurso
             * cuando Imagick no está disponible.
             */
            if (extension_loaded('gd')) {
                try {
                    // Crear imagen desde string binario
                    $image = imagecreatefromstring($tiffBinario);

                    if ($image !== false) {
                        // Capturar salida PNG en buffer
                        ob_start();
                        imagepng($image);
                        $pngData = ob_get_contents();
                        ob_end_clean();

                        // Liberar memoria
                        imagedestroy($image);

                        Log::info('Conversión TIFF: Exitosa con GD nativo', [
                            'ancho' => imagesx($image),
                            'alto' => imagesy($image)
                        ]);

                        return base64_encode($pngData);
                    }
                } catch (\Exception $e) {
                    Log::warning('Conversión TIFF: GD falló', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            //Todos los métodos fallaron
            Log::error('Conversión TIFF: Todos los métodos fallaron', [
                'imagick_disponible' => extension_loaded('imagick'),
                'gd_disponible' => extension_loaded('gd'),
                'tamaño_tiff' => strlen($tiffBinario)
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Conversión TIFF: Error general', [
                'error' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]);
            return null;
        }
    }

    /**
     * Procesa una guía de Servientrega consultando la API y almacenando datos
     *
     * Realiza el flujo completo de procesamiento:
     * 1. Validación del número de guía
     * 2. Consulta a la API de Servientrega
     * 3. Parseo de respuesta XML
     * 4. Conversión de imagen TIFF si existe
     * 5. Almacenamiento en base de datos
     *
     * @param string $numeroGuia Número de guía a consultar (solo dígitos)
     * @param bool $logAcceso Si se debe registrar el acceso para auditoría
     * @return array Array con 'array' (datos API) y 'trackingRecord' (modelo BD)
     *
     * @throws \Exception Si el formato de guía es inválido
     * @throws \Exception Si la guía no se encuentra en el sistema
     * @throws \Exception Si hay errores en el almacenamiento
     *
     * @example
     * try {
     *     $resultado = $this->procesarGuia('123456789');
     *     $datosAPI = $resultado['array'];
     *     $modelo = $resultado['trackingRecord'];
     * } catch (\Exception $e) {
     *     Log::error('Error procesando guía: ' . $e->getMessage());
     * }
     *
     * @see https://wssismilenio.servientrega.com/wsrastreoenvios/wsrastreoenvios.asmx
     */
    private function procesarGuia($numeroGuia, $logAcceso = false)
    {
        // Validar formato de número de guía
        if (!preg_match('/^[0-9]+$/', $numeroGuia)) {
            throw new \Exception('Formato de guía inválido: debe contener solo números');
        }

        // Log de auditoría si es requerido
        if ($logAcceso) {
            Log::info('Acceso directo a guía', [
                'numero_guia' => $numeroGuia,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()
            ]);
        }

        /*
         * Consulta a la API de Servientrega
         *
         * La API devuelve XML con toda la información del envío
         * incluyendo movimientos e imagen del comprobante en TIFF.
         */
        try {
            $response = Http::timeout(60)->get(
                "https://wssismilenio.servientrega.com/wsrastreoenvios/wsrastreoenvios.asmx/ConsultarGuiaExterno",
                ['NumeroGuia' => $numeroGuia]
            );

            if (!$response->successful()) {
                throw new \Exception("Guía {$numeroGuia} no encontrada en el sistema de Servientrega");
            }
        } catch (\Exception $e) {
            Log::error('Error consultando API Servientrega', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage(),
                'status_code' => $response->status() ?? 'N/A'
            ]);
            throw $e;
        }

        /*
         * Procesamiento de respuesta XML
         *
         * La API devuelve XML que debe ser convertido a array
         * para facilitar el manejo de datos en PHP.
         */
        try {
            $xml = simplexml_load_string($response->body());

            if ($xml === false) {
                throw new \Exception('Respuesta XML inválida de la API');
            }

            $array = json_decode(json_encode($xml), true);
        } catch (\Exception $e) {
            Log::error('Error procesando XML de respuesta', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage(),
                'xml_snippet' => substr($response->body(), 0, 200)
            ]);
            throw new \Exception('Error procesando respuesta de la API');
        }

        /*
         * Normalización de movimientos
         *
         * La API puede devolver un solo movimiento como objeto
         * o múltiples movimientos como array. Se normaliza a array.
         */
        $movimientos = $array['Mov']['InformacionMov'] ?? [];
        if (!is_array($movimientos)) {
            $movimientos = [$movimientos];
        }

        /*
         * Procesamiento de imagen del comprobante
         *
         * Si existe imagen en la respuesta (formato TIFF), se convierte
         * a PNG para compatibilidad con navegadores web.
         */
        $imagenPngBase64 = null;
        if (isset($array['Imagen']) && !empty($array['Imagen'])) {
            $imagenBase64Original = is_array($array['Imagen'])
                ? $array['Imagen'][0]
                : $array['Imagen'];

            Log::info('Procesando imagen de comprobante', [
                'numero_guia' => $numeroGuia,
                'tamaño_original' => strlen($imagenBase64Original)
            ]);

            $imagenPngBase64 = $this->convertirTiffAPng($imagenBase64Original);

            if ($imagenPngBase64) {
                Log::info('Imagen convertida exitosamente', [
                    'numero_guia' => $numeroGuia,
                    'tamaño_png' => strlen($imagenPngBase64)
                ]);
            } else {
                Log::warning('No se pudo convertir imagen del comprobante', [
                    'numero_guia' => $numeroGuia
                ]);
            }
        }

        /*
         * Preparación de datos para almacenamiento
         *
         * Se mapean todos los campos de la API a la estructura
         * de la base de datos, aplicando limpieza y normalización.
         */
        $identificador = ['numero_guia' => $numeroGuia];
        $datos = [
            // Información básica del envío
            'fec_env' => $this->limpiarValor($array['FecEnv'] ?? null),
            'num_pie' => $this->limpiarValor($array['NumPie'] ?? null),

            // Información del remitente
            'ciu_remitente' => $this->limpiarValor($array['CiuRem'] ?? null),
            'nom_remitente' => $this->limpiarValor($array['NomRem'] ?? null),
            'dir_remitente' => $this->limpiarValor($array['DirRem'] ?? null),

            // Información del destinatario
            'ciu_destinatario' => $this->limpiarValor($array['CiuDes'] ?? null),
            'nom_destinatario' => $this->limpiarValor($array['NomDes'] ?? null),
            'dir_destinatario' => $this->limpiarValor($array['DirDes'] ?? null),

            // Estado del envío
            'id_estado_actual' => $this->limpiarValor($array['IdEstAct'] ?? null),
            'estado_actual' => $this->limpiarValor($array['EstAct'] ?? null),
            'fecha_estado' => $this->limpiarValor($array['FecEst'] ?? null),

            // Información adicional
            'nom_receptor' => $this->limpiarValor($array['NomRec'] ?? null),
            'num_cun' => $this->limpiarValor($array['NumCun'] ?? null),
            'regimen' => $this->limpiarValor($array['Regime'] ?? null),
            'placa' => $this->limpiarValor($array['Placa'] ?? null),
            'id_gps' => $this->limpiarValor($array['IdGPS'] ?? null),
            'forma_pago' => $this->limpiarValor($array['FormPago'] ?? null),
            'nomb_producto' => $this->limpiarValor($array['NomProducto'] ?? null),
            'fecha_probable' => $this->limpiarValor($array['FechaProbable'] ?? null),

            // Imagen convertida y movimientos
            'imagen_base64' => $imagenPngBase64,
            'movimientos' => $movimientos,
        ];

        /*
         * Almacenamiento en base de datos
         *
         * Utiliza updateOrCreate para evitar duplicados,
         * actualizando registro existente o creando nuevo.
         */
        try {
            $trackingRecord = TrackingServientrega::updateOrCreate(
                $identificador,
                $datos
            );

            Log::info('Guía procesada y almacenada', [
                'numero_guia' => $numeroGuia,
                'id_registro' => $trackingRecord->id,
                'imagen_procesada' => !empty($trackingRecord->imagen_base64),
                'movimientos_count' => count($movimientos)
            ]);
        } catch (\Exception $e) {
            Log::error('Error almacenando en base de datos', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage(),
                'datos' => $datos
            ]);
            throw new \Exception('Error almacenando información de la guía');
        }

        return [
            'array' => $array,
            'trackingRecord' => $trackingRecord
        ];
    }

    /**
     * Maneja consulta de guía vía formulario web
     *
     * Procesa peticiones POST desde formularios de consulta,
     * valida la entrada y devuelve vista con resultados.
     *
     * @param \Illuminate\Http\Request $request Petición HTTP con datos del formulario
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException Si la validación falla
     *
     * Validaciones aplicadas:
     * - numero_guia: requerido, debe ser numérico
     *
     * Respuestas posibles:
     * - Vista 'resultados' con datos de la guía (éxito)
     * - Redirect back con errores (fallo)
     *
     * @example
     * // Formulario HTML
     * <form method="POST" action="/consultar">
     *     @csrf
     *     <input name="numero_guia" value="123456789" required>
     *     <button type="submit">Consultar</button>
     * </form>
     */
    public function consultarGuia(Request $request)
    {
        // Validación de entrada
        $request->validate([
            'numero_guia' => 'required|numeric'
        ], [
            'numero_guia.required' => 'El número de guía es obligatorio',
            'numero_guia.numeric' => 'El número de guía debe contener solo números'
        ]);

        $numeroGuia = $request->input('numero_guia');

        try {
            // Procesar guía y obtener datos
            $resultado = $this->procesarGuia($numeroGuia);

            // Retornar vista con resultados
            return view('resultados', [
                'respuesta' => $resultado['array'],
                'trackingRecord' => $resultado['trackingRecord'],
                'numeroGuia' => $numeroGuia
            ]);
        } catch (\Exception $e) {
            Log::error('Error en consulta por formulario', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return back()
                ->withInput()
                ->withErrors(['Error al consultar la guía: ' . $e->getMessage()]);
        }
    }

    /**
     * Maneja consulta directa de guía vía URL
     *
     * Procesa peticiones GET directas a URLs como /guia/{numero},
     * útil para enlaces compartibles y bookmarking.
     *
     * @param string $numeroGuia Número de guía desde parámetro de ruta
     * @param \Illuminate\Http\Request $request Petición HTTP para obtener referer
     * @return \Illuminate\View\View Vista con detalles de la guía o error
     *
     * Características especiales:
     * - Registra acceso para auditoría
     * - Maneja URL de origen para navegación
     * - Vista específica para acceso directo
     * - Manejo de errores con vista dedicada
     *
     * @example
     * // URLs soportadas:
     * GET /guia/123456789
     * GET /guia/123456789?origen=https://example.com/search
     *
     * // Vistas devueltas:
     * - 'guia-detalle' (éxito)
     * - 'guia-detalle-error' (error)
     */
    public function verGuia($numeroGuia, Request $request)
    {
        try {
            // Procesar guía con logging de acceso habilitado
            $resultado = $this->procesarGuia($numeroGuia, true);

            // Determinar URL de origen para navegación de retorno
            $origen = $request->get('origen', $request->header('referer', '/'));

            return view('guia-detalle', [
                'respuesta' => $resultado['array'],
                'trackingRecord' => $resultado['trackingRecord'],
                'numeroGuia' => $numeroGuia,
                'urlOrigen' => $origen
            ]);
        } catch (\Exception $e) {
            Log::error('Error en consulta directa', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Vista específica para errores en consulta directa
            return view('guia-detalle-error', [
                'mensaje' => $e->getMessage(),
                'numeroGuia' => $numeroGuia,
                'urlOrigen' => $request->get('origen', $request->header('referer', '/'))
            ]);
        }
    }
}
