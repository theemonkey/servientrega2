<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\TrackingServientrega;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class TrackingServientregaController extends Controller
{
    // Helper para limpiar valores antes de guardar
    private function limpiarValor($valor)
    {
        if (is_array($valor)) {
            return implode(', ', array_filter($valor));
        }
        return $valor ?? null;
    }

    // Detectar y separar múltiples imágenes base64 concatenadas
    private function separarImagenesBase64($imagenCompleta)
    {
        try {
            Log::info('=== SEPARACIÓN DE IMÁGENES ===', [
                'longitud_total' => strlen($imagenCompleta)
            ]);

            $imagenLimpia = preg_replace('/[^A-Za-z0-9+\/=]/', '', $imagenCompleta);

            $headersTiff = ['SUkq', 'TU0A'];
            $posiciones = [];

            foreach ($headersTiff as $header) {
                $offset = 0;
                while (($pos = strpos($imagenLimpia, $header, $offset)) !== false) {
                    $posiciones[] = $pos;
                    $offset = $pos + 1;
                }
            }

            if (count($posiciones) > 1) {
                sort($posiciones);
                $primeraImagen = substr($imagenLimpia, $posiciones[0], $posiciones[1] - $posiciones[0]);
                Log::info('Múltiples imágenes, usando primera', ['longitud' => strlen($primeraImagen)]);
                return $primeraImagen;
            }

            return $imagenLimpia;

        } catch (\Exception $e) {
            Log::error('Error en separación: ' . $e->getMessage());
            return $imagenCompleta;
        }
    }

    // Validar TIFF
    private function validarTiffBase64($base64)
    {
        try {
            $binary = base64_decode($base64, true);
            if ($binary === false || strlen($binary) < 8) {
                return ['valido' => false, 'error' => 'Base64 o datos inválidos'];
            }

            $magicBytes = substr($binary, 0, 4);
            $isTiffLE = ($magicBytes === "II*\x00");
            $isTiffBE = ($magicBytes === "MM\x00*");

            return [
                'valido' => $isTiffLE || $isTiffBE,
                'tamaño_binario' => strlen($binary),
                'magic_bytes' => bin2hex($magicBytes),
                'es_little_endian' => $isTiffLE,
                'es_big_endian' => $isTiffBE
            ];

        } catch (\Exception $e) {
            return ['valido' => false, 'error' => $e->getMessage()];
        }
    }

    // NUEVO: Análisis profundo del TIFF
    private function analizarTiffProfundo($tiffBinario)
    {
        try {
            Log::info('=== ANÁLISIS PROFUNDO TIFF ===');

            $longitud = strlen($tiffBinario);
            Log::info('Tamaño binario: ' . $longitud . ' bytes');

            if ($longitud < 8) {
                return ['error' => 'TIFF muy pequeño'];
            }

            // Analizar header TIFF completo
            $header = substr($tiffBinario, 0, 8);
            $byteOrder = substr($header, 0, 2);
            $magic = substr($header, 2, 2);
            $ifdOffset = unpack('V', substr($header, 4, 4))[1]; // Little-endian

            $analisis = [
                'byte_order' => bin2hex($byteOrder),
                'magic_number' => bin2hex($magic),
                'ifd_offset' => $ifdOffset,
                'es_little_endian' => $byteOrder === 'II',
                'es_big_endian' => $byteOrder === 'MM',
                'magic_valido' => $magic === "*\x00",
                'tamaño_total' => $longitud
            ];

            Log::info('Header TIFF:', $analisis);

            // Verificar si el IFD offset es válido
            if ($ifdOffset >= $longitud) {
                $analisis['error'] = 'IFD offset fuera de rango';
                Log::error('IFD offset inválido', $analisis);
                return $analisis;
            }

            // Leer primer IFD
            if ($ifdOffset + 2 <= $longitud) {
                $numEntries = unpack('v', substr($tiffBinario, $ifdOffset, 2))[1];
                $analisis['num_ifd_entries'] = $numEntries;

                Log::info("IFD contiene $numEntries entradas");

                // Leer algunas entradas importantes
                $entradas = [];
                $pos = $ifdOffset + 2;

                for ($i = 0; $i < min($numEntries, 5) && $pos + 12 <= $longitud; $i++) {
                    $entrada = substr($tiffBinario, $pos, 12);
                    $tag = unpack('v', substr($entrada, 0, 2))[1];
                    $tipo = unpack('v', substr($entrada, 2, 2))[1];
                    $count = unpack('V', substr($entrada, 4, 4))[1];
                    $value = unpack('V', substr($entrada, 8, 4))[1];

                    $entradas[] = [
                        'tag' => $tag,
                        'tipo' => $tipo,
                        'count' => $count,
                        'value' => $value
                    ];

                    // Tags importantes
                    if ($tag == 256) $analisis['ancho'] = $value;
                    if ($tag == 257) $analisis['alto'] = $value;
                    if ($tag == 258) $analisis['bits_per_sample'] = $value;
                    if ($tag == 259) $analisis['compression'] = $value;
                    if ($tag == 262) $analisis['photometric'] = $value;

                    $pos += 12;
                }

                $analisis['entradas_ifd'] = $entradas;
            }

            return $analisis;
        } catch (\Exception $e) {
            Log::error('Error en análisis TIFF: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    // NUEVO: Guardar TIFF como archivo para análisis manual
    private function guardarTiffParaAnalisis($tiffBinario, $numeroGuia)
    {
        try {
            $tempDir = storage_path('app/debug');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $archivo = $tempDir . "/guia_{$numeroGuia}_" . date('Y-m-d_H-i-s') . '.tiff';

            $bytes = file_put_contents($archivo, $tiffBinario);

            Log::info('TIFF guardado para análisis', [
                'archivo' => $archivo,
                'bytes' => $bytes,
                'comando_analisis' => "file $archivo"
            ]);

            return $archivo;
        } catch (\Exception $e) {
            Log::error('Error guardando TIFF: ' . $e->getMessage());
            return null;
        }
    }

    // NUEVO: Probar diferentes decodificaciones del base64
    private function probarDecodificacionesBase64($base64Original)
    {
        Log::info('=== PROBANDO DECODIFICACIONES BASE64 ===');

        $pruebas = [
            'original' => $base64Original,
            'sin_espacios' => preg_replace('/\s+/', '', $base64Original),
            'sin_saltos' => str_replace(["\r", "\n", "\t"], '', $base64Original),
            'solo_base64' => preg_replace('/[^A-Za-z0-9+\/=]/', '', $base64Original)
        ];

        $resultados = [];

        foreach ($pruebas as $nombre => $base64) {
            try {
                $binario = base64_decode($base64, true);

                if ($binario === false) {
                    $resultados[$nombre] = ['valido' => false, 'error' => 'Decodificación falló'];
                    continue;
                }

                $magic = strlen($binario) >= 4 ? bin2hex(substr($binario, 0, 4)) : 'muy_corto';

                $resultados[$nombre] = [
                    'valido' => true,
                    'longitud_base64' => strlen($base64),
                    'longitud_binario' => strlen($binario),
                    'magic_bytes' => $magic,
                    'es_tiff' => in_array($magic, ['49492a00', '4d4d002a'])
                ];
            } catch (\Exception $e) {
                $resultados[$nombre] = ['valido' => false, 'error' => $e->getMessage()];
            }
        }

        Log::info('Resultados decodificaciones:', $resultados);
        return $resultados;
    }

    // Aplicar tipo de decodificación específica
    private function aplicarDecodificacion($base64Original, $tipo)
    {
        switch ($tipo) {
            case 'sin_espacios':
                return preg_replace('/\s+/', '', $base64Original);
            case 'sin_saltos':
                return str_replace(["\r", "\n", "\t"], '', $base64Original);
            case 'solo_base64':
                return preg_replace('/[^A-Za-z0-9+\/=]/', '', $base64Original);
            default:
                return $base64Original;
        }
    }

    // Intentar conversión con Intervention Image
    private function intentarConversionInterventionImage($tiffBinario)
    {
        try {
            Log::info('🔄 Probando Intervention Image');

            $manager = new ImageManager(new Driver());
            $image = $manager->read($tiffBinario);

            Log::info('✅ Intervention Image - imagen cargada', [
                'ancho' => $image->width(),
                'alto' => $image->height()
            ]);

            $pngBinario = $image->encode('png')->toString();
            return base64_encode($pngBinario);
        } catch (\Exception $e) {
            Log::error('❌ Intervention Image falló: ' . $e->getMessage());
            return null;
        }
    }

    // Intentar conversión con GD
    private function intentarConversionGD($tiffBinario)
    {
        try {
            Log::info('🔄 Probando GD');

            if (!extension_loaded('gd')) {
                Log::info('GD no disponible');
                return null;
            }

            $image = imagecreatefromstring($tiffBinario);

            if ($image === false) {
                Log::warning('GD no pudo crear imagen');
                return null;
            }

            Log::info('✅ GD - imagen creada', [
                'ancho' => imagesx($image),
                'alto' => imagesy($image)
            ]);

            ob_start();
            imagepng($image);
            $pngData = ob_get_contents();
            ob_end_clean();

            imagedestroy($image);

            return base64_encode($pngData);
        } catch (\Exception $e) {
            Log::error('❌ GD falló: ' . $e->getMessage());
            return null;
        }
    }

    // Conversión con análisis completo
    private function convertirConAnalisisCompleto($tiffBinario, $analisis)
    {
        Log::info('=== CONVERSIÓN CON INFORMACIÓN DE ANÁLISIS ===');

        // Intentar diferentes estrategias basadas en el análisis
        if (isset($analisis['compression'])) {
            Log::info('TIFF tiene compresión: ' . $analisis['compression']);

            // 1 = Sin compresión, 5 = LZW, 6 = JPEG, 32773 = PackBits
            switch ($analisis['compression']) {
                case 1:
                    Log::info('TIFF sin compresión - debería ser fácil de convertir');
                    break;
                case 5:
                    Log::info('TIFF con compresión LZW - requiere soporte específico');
                    break;
                case 6:
                    Log::info('TIFF con compresión JPEG - híbrido');
                    break;
                default:
                    Log::warning('Compresión TIFF desconocida: ' . $analisis['compression']);
            }
        }

        // Intentar conversión con Intervention Image
        $resultado = $this->intentarConversionInterventionImage($tiffBinario);
        if ($resultado) {
            Log::info('✅ Conversión exitosa con Intervention Image');
            return $resultado;
        }

        // Intentar con GD
        $resultado = $this->intentarConversionGD($tiffBinario);
        if ($resultado) {
            Log::info('✅ Conversión exitosa con GD');
            return $resultado;
        }

        Log::error('❌ Conversión falló con todos los métodos');
        return null;
    }

    // VERSIÓN MEJORADA CON ANÁLISIS COMPLETO
    private function convertirTiffAPngConAnalisis($tiffBase64)
    {
        try {
            Log::info('=== CONVERSIÓN CON ANÁLISIS COMPLETO ===');

            // 1. Probar diferentes decodificaciones
            $decodificaciones = $this->probarDecodificacionesBase64($tiffBase64);

            // Encontrar la mejor decodificación
            $mejorDecodificacion = null;
            foreach ($decodificaciones as $nombre => $resultado) {
                if ($resultado['valido'] && isset($resultado['es_tiff']) && $resultado['es_tiff']) {
                    $mejorDecodificacion = $nombre;
                    Log::info("✅ Mejor decodificación encontrada: $nombre");
                    break;
                }
            }

            if (!$mejorDecodificacion) {
                Log::error('❌ No se encontró decodificación TIFF válida');
                return null;
            }

            // 2. Usar la mejor decodificación
            $base64Limpio = $this->aplicarDecodificacion($tiffBase64, $mejorDecodificacion);
            $tiffBinario = base64_decode($base64Limpio, true);

            // 3. Análisis profundo del TIFF
            $analisisTiff = $this->analizarTiffProfundo($tiffBinario);
            Log::info('Análisis TIFF completo:', $analisisTiff);

            // 4. Guardar archivo para análisis manual
            $archivoGuardado = $this->guardarTiffParaAnalisis($tiffBinario, 'debug');

            // 5. Verificar si el TIFF tiene problemas conocidos
            if (isset($analisisTiff['error'])) {
                Log::error('TIFF tiene errores estructurales: ' . $analisisTiff['error']);
                return null;
            }

            // 6. Intentar conversión con información del análisis
            $resultado = $this->convertirConAnalisisCompleto($tiffBinario, $analisisTiff);

            return $resultado;
        } catch (\Exception $e) {
            Log::error('Error en conversión con análisis: ' . $e->getMessage());
            return null;
        }
    }

    // Metodo de conversion alternativa


    // Generar recomendación basada en resultados
    private function generarRecomendacion($decodificaciones, $resultado)
    {
        if ($resultado !== null) {
            return "✅ Conversión exitosa - el sistema actual funciona";
        }

        $hayTiffValido = false;
        foreach ($decodificaciones as $dec) {
            if (isset($dec['es_tiff']) && $dec['es_tiff']) {
                $hayTiffValido = true;
                break;
            }
        }

        if (!$hayTiffValido) {
            return "❌ El base64 no contiene un TIFF válido - problema en la API";
        }

        return "⚠️ TIFF válido pero no se puede convertir - INSTALAR IMAGEMAGICK recomendado";
    }

    // MÉTODO PRINCIPAL - Procesa cualquier guía
    private function procesarGuia($numeroGuia, $logAcceso = false)
    {
        Log::info('=== PROCESAMIENTO GUÍA CON ANÁLISIS ===', ['guia' => $numeroGuia]);

        // Aumentar límites
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);

        if (!preg_match('/^[0-9]+$/', $numeroGuia)) {
            throw new \Exception('Formato de guía inválido');
        }

        $response = Http::timeout(60)->get("https://wssismilenio.servientrega.com/wsrastreoenvios/wsrastreoenvios.asmx/ConsultarGuiaExterno", [
            'NumeroGuia' => $numeroGuia
        ]);

        if (!$response->successful()) {
            throw new \Exception('Guía no encontrada en el sistema');
        }

        $xml = simplexml_load_string($response->body());
        $array = json_decode(json_encode($xml), true);

        $movimientos = $array['Mov']['InformacionMov'] ?? [];
        if (!is_array($movimientos)) {
            $movimientos = [$movimientos];
        }

        // Procesar imagen con análisis completo
        $imagenPngBase64 = null;
        if (isset($array['Imagen']) && !empty($array['Imagen'])) {
            $imagenBase64Original = is_array($array['Imagen']) ? $array['Imagen'][0] : $array['Imagen'];
            $imagenBase64Original = preg_replace('/\s+/', '', $imagenBase64Original);

            // Forzar conversion
            Log::info('=== Iniciando conversion forzada ===', [
                'longitud_imagen_original' => strlen($imagenBase64Original)
            ]);

            $imagenPngBase64 = $this->convertirTiffAPngConAnalisis($imagenBase64Original);

            if ($imagenPngBase64) {
                Log::info('✅ Conversión forzada exitosa', [
                    'longitud_png_base64' => strlen($imagenPngBase64)
                ]);
            } else {
                Log::error('❌ Conversión forzada fallida - Intentando metodos alternativos');
                // Método alternativo simple
                $imagenPngBase64 = $this->conversionAlternativa($imagenBase64Original);
            }

            Log::info('Resultado final imagen', [
                'conversion_exitosa' => !is_null($imagenPngBase64),
                'longitud_resultado' => !is_null($imagenPngBase64) ? strlen($imagenPngBase64) : 0
            ]);
        }

        // Guardar datos
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
            'imagen_base64' => $imagenPngBase64,
            'movimientos' => $movimientos,
        ];

        try {
            $trackingRecord = TrackingServientrega::updateOrCreate($identificador, $datos);

            Log::info('✅ Guardado en BD', [
                'id' => $trackingRecord->id,
                'imagen_guardada' => !empty($trackingRecord->imagen_base64)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error guardando en BD: ' . $e->getMessage());
            throw $e;
        }

        return [
            'array' => $array,
            'trackingRecord' => $trackingRecord
        ];
    }

    // Consulta de guía vía formulario
    public function consultarGuia(Request $request)
    {
        $request->validate(['numero_guia' => 'required|numeric']);
        $numeroGuia = $request->input('numero_guia');

        try {
            $resultado = $this->procesarGuia($numeroGuia);
            return view('resultados', [
                'respuesta' => $resultado['array'],
                'trackingRecord' => $resultado['trackingRecord'],
            ]);
        } catch (\Exception $e) {
            Log::error('Error en consultarGuia', [
                'guia' => $numeroGuia,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['Error al consultar la guía: ' . $e->getMessage()]);
        }
    }

    // Consulta de guía vía directa
    public function verGuia($numeroGuia, Request $request)
    {
        try {
            $resultado = $this->procesarGuia($numeroGuia, true);
            $origen = $request->get('origen', $request->header('referer', '/'));

            return view('guia-detalle', [
                'respuesta' => $resultado['array'],
                'trackingRecord' => $resultado['trackingRecord'],
                'numeroGuia' => $numeroGuia,
                'urlOrigen' => $origen
            ]);
        } catch (\Exception $e) {
            Log::error('Error consultando guía directa', [
                'guia' => $numeroGuia,
                'error' => $e->getMessage()
            ]);

            return view('guia-detalle-error', [
                'mensaje' => $e->getMessage(),
                'numeroGuia' => $numeroGuia,
                'urlOrigen' => $request->get('origen', $request->header('referer', '/'))
            ]);
        }
    }

    // Debug básico
    public function debugImagen($numeroGuia)
    {
        try {
            $response = Http::timeout(60)->get("https://wssismilenio.servientrega.com/wsrastreoenvios/wsrastreoenvios.asmx/ConsultarGuiaExterno", [
                'NumeroGuia' => $numeroGuia
            ]);

            $xml = simplexml_load_string($response->body());
            $array = json_decode(json_encode($xml), true);

            if (isset($array['Imagen']) && !empty($array['Imagen'])) {
                $imagenOriginal = is_array($array['Imagen']) ? $array['Imagen'][0] : $array['Imagen'];
                $imagenLimpia = preg_replace('/\s+/', '', $imagenOriginal);

                $validacion = $this->validarTiffBase64($imagenLimpia);
                $resultado = $this->convertirTiffAPngConAnalisis($imagenLimpia);

                return response()->json([
                    'success' => true,
                    'guia' => $numeroGuia,
                    'imagen_info' => [
                        'tiene_imagen_api' => true,
                        'longitud_original' => strlen($imagenOriginal),
                        'longitud_limpia' => strlen($imagenLimpia),
                        'validacion_tiff' => $validacion,
                        'conversion_exitosa' => !is_null($resultado),
                        'longitud_resultado' => !is_null($resultado) ? strlen($resultado) : 0
                    ],
                    'mensaje' => 'Revisa logs para análisis básico'
                ]);
            }

            return response()->json(['success' => false, 'mensaje' => 'Sin imagen']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // MÉTODO DEBUG SÚPER COMPLETO
    public function debugImagenCompleto($numeroGuia)
    {
        try {
            Log::info('=== DEBUG SÚPER COMPLETO ===', ['guia' => $numeroGuia]);

            $response = Http::timeout(60)->get("https://wssismilenio.servientrega.com/wsrastreoenvios/wsrastreoenvios.asmx/ConsultarGuiaExterno", [
                'NumeroGuia' => $numeroGuia
            ]);

            $xml = simplexml_load_string($response->body());
            $array = json_decode(json_encode($xml), true);

            if (!isset($array['Imagen']) || empty($array['Imagen'])) {
                return response()->json(['success' => false, 'mensaje' => 'Sin imagen']);
            }

            $imagenOriginal = is_array($array['Imagen']) ? $array['Imagen'][0] : $array['Imagen'];

            // Análisis completo
            $decodificaciones = $this->probarDecodificacionesBase64($imagenOriginal);
            $resultado = $this->convertirTiffAPngConAnalisis($imagenOriginal);

            return response()->json([
                'success' => true,
                'guia' => $numeroGuia,
                'analisis_completo' => [
                    'longitud_original' => strlen($imagenOriginal),
                    'decodificaciones' => $decodificaciones,
                    'conversion_exitosa' => !is_null($resultado),
                    'longitud_resultado' => !is_null($resultado) ? strlen($resultado) : 0
                ],
                'configuracion_servidor' => [
                    'intervention_image' => class_exists('Intervention\Image\ImageManager'),
                    'imagick' => class_exists('Imagick'),
                    'gd' => extension_loaded('gd'),
                    'temp_dir_writable' => is_writable(sys_get_temp_dir()),
                    'storage_writable' => is_writable(storage_path('app')),
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time')
                ],
                'recomendacion' => $this->generarRecomendacion($decodificaciones, $resultado),
                'archivo_tiff_guardado' => storage_path('app/debug/guia_debug_*.tiff'),
                'mensaje' => 'Revisa logs para análisis completo del TIFF y estructura'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
