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
 * Controlador optimizado para guardar PNG binario en BD
 * Mucho más liviano que base64
 */
class TrackingServientregaController extends Controller
{
    private const IMAGE_MAX_WIDTH = 800;
    private const IMAGE_MAX_HEIGHT = 1200;
    private const IMAGE_QUALITY = 85;

    private function limpiarValor($valor)
    {
        if (is_array($valor)) {
            return implode(', ', array_filter($valor));
        }
        return $valor ?? null;
    }

    /**
     * Convierte TIFF a PNG y retorna BINARIO (no base64)
     */
    private function convertirTiffAPngBinario($tiffBase64, $numeroGuia)
    {
        try {
            Log::info('=== CONVERSIÓN TIFF → PNG BINARIO ===', [
                'numero_guia' => $numeroGuia,
                'tamaño_tiff_base64_kb' => round(strlen($tiffBase64) / 1024, 2)
            ]);

            // Decodificar TIFF
            $base64Limpio = preg_replace('/[^A-Za-z0-9+\/=]/', '', $tiffBase64);
            $tiffBinario = base64_decode($base64Limpio, true);

            if ($tiffBinario === false) {
                Log::warning('No se pudo decodificar TIFF base64');
                return null;
            }

            $pngBinario = null;
            $metodoUsado = '';

            /*
             * MÉTODO 1: Imagick
             */
            if (extension_loaded('imagick') && class_exists('Imagick')) {
                try {
                    $imagick = new \Imagick();
                    $imagick->readImageBlob($tiffBinario);

                    Log::info('TIFF cargado con Imagick', [
                        'ancho_original' => $imagick->getImageWidth(),
                        'alto_original' => $imagick->getImageHeight()
                    ]);

                    // Optimizar tamaño
                    if (
                        $imagick->getImageWidth() > self::IMAGE_MAX_WIDTH ||
                        $imagick->getImageHeight() > self::IMAGE_MAX_HEIGHT
                    ) {
                        $imagick->resizeImage(
                            self::IMAGE_MAX_WIDTH,
                            self::IMAGE_MAX_HEIGHT,
                            \Imagick::FILTER_LANCZOS,
                            1,
                            true
                        );

                        Log::info('Imagen redimensionada', [
                            'nuevo_ancho' => $imagick->getImageWidth(),
                            'nuevo_alto' => $imagick->getImageHeight()
                        ]);
                    }

                    // Configurar PNG optimizado
                    $imagick->setImageFormat('png');
                    $imagick->setImageCompressionQuality(self::IMAGE_QUALITY);
                    $imagick->stripImage(); // Remover metadatos

                    // CLAVE: Obtener PNG como binario (no base64)
                    $pngBinario = $imagick->getImageBlob();
                    $metodoUsado = 'Imagick';

                    $imagick->clear();
                    $imagick->destroy();
                } catch (\Exception $e) {
                    Log::warning('Imagick falló: ' . $e->getMessage());
                }
            }

            /*
             * MÉTODO 2: Intervention Image
             */
            if (!$pngBinario) {
                try {
                    $manager = extension_loaded('imagick')
                        ? new ImageManager(new ImagickDriver())
                        : new ImageManager(new GdDriver());

                    $image = $manager->read($tiffBinario);

                    // Redimensionar
                    if (
                        $image->width() > self::IMAGE_MAX_WIDTH ||
                        $image->height() > self::IMAGE_MAX_HEIGHT
                    ) {
                        $image->resize(self::IMAGE_MAX_WIDTH, self::IMAGE_MAX_HEIGHT, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                    }

                    // CLAVE: toString() nos da el binario directamente
                    $pngBinario = $image->toPng()->toString();
                    $metodoUsado = 'Intervention Image';
                } catch (\Exception $e) {
                    Log::warning('Intervention Image falló: ' . $e->getMessage());
                }
            }

            /*
             * MÉTODO 3: GD
             */
            if (!$pngBinario && extension_loaded('gd')) {
                try {
                    $image = imagecreatefromstring($tiffBinario);

                    if ($image !== false) {
                        $width = imagesx($image);
                        $height = imagesy($image);

                        // Redimensionar si es necesario
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
                            $image = $optimizedImage;
                        }

                        // CLAVE: Capturar PNG binario
                        ob_start();
                        imagepng($image, null, 8);
                        $pngBinario = ob_get_contents();
                        ob_end_clean();
                        imagedestroy($image);

                        $metodoUsado = 'GD';
                    }
                } catch (\Exception $e) {
                    Log::warning('GD falló: ' . $e->getMessage());
                }
            }

            if (!$pngBinario) {
                Log::error(' Conversión falló completamente');
                return null;
            }

            // Verificar que es PNG válido
            $isPngValid = substr($pngBinario, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";

            Log::info(' CONVERSIÓN EXITOSA', [
                'numero_guia' => $numeroGuia,
                'metodo' => $metodoUsado,
                'tamaño_tiff_kb' => round(strlen($tiffBinario) / 1024, 2),
                'tamaño_png_binario_kb' => round(strlen($pngBinario) / 1024, 2),
                'tamaño_que_sería_base64_kb' => round(strlen(base64_encode($pngBinario)) / 1024, 2),
                'ahorro_vs_base64' => round((1 - strlen($pngBinario) / strlen(base64_encode($pngBinario))) * 100, 2) . '%',
                'png_válido' => $isPngValid
            ]);

            // RETORNAMOS BINARIO (no base64)
            return $pngBinario;
        } catch (\Exception $e) {
            Log::error(' Error en conversión', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Procesar guía con PNG binario
     */
    private function procesarGuia($numeroGuia, $logAcceso = false)
    {
        if (!preg_match('/^[0-9]+$/', $numeroGuia)) {
            throw new \Exception('Formato de guía inválido: debe contener solo números');
        }

        if ($logAcceso) {
            Log::info('Acceso directo a guía', ['numero_guia' => $numeroGuia]);
        }

        // Consultar API
        try {
            $response = Http::timeout(60)->get(
                "https://wssismilenio.servientrega.com/wsrastreoenvios/wsrastreoenvios.asmx/ConsultarGuiaExterno",
                ['NumeroGuia' => $numeroGuia]
            );

            if (!$response->successful()) {
                throw new \Exception("Guía {$numeroGuia} no encontrada en el sistema");
            }
        } catch (\Exception $e) {
            Log::error('Error API', ['numero_guia' => $numeroGuia, 'error' => $e->getMessage()]);
            throw $e;
        }

        // Procesar XML
        try {
            $xml = simplexml_load_string($response->body());
            if ($xml === false) {
                throw new \Exception('Respuesta XML inválida');
            }
            $array = json_decode(json_encode($xml), true);
        } catch (\Exception $e) {
            Log::error('Error XML', ['numero_guia' => $numeroGuia, 'error' => $e->getMessage()]);
            throw new \Exception('Error procesando respuesta de la API');
        }

        // Normalizar movimientos
        $movimientos = $array['Mov']['InformacionMov'] ?? [];
        if (!is_array($movimientos)) {
            $movimientos = [$movimientos];
        }

        /*
         * ¡CAMBIO PRINCIPAL!
         * Ahora guardamos PNG BINARIO (no base64)
         */
        $imagenPngBinario = null;
        if (isset($array['Imagen']) && !empty($array['Imagen'])) {
            $imagenTiffOriginal = is_array($array['Imagen'])
                ? $array['Imagen'][0]
                : $array['Imagen'];

            Log::info('Procesando imagen para optimización', [
                'numero_guia' => $numeroGuia,
                'tamaño_tiff_original_kb' => round(strlen($imagenTiffOriginal) / 1024, 2)
            ]);

            // Convertir a PNG binario (mucho más liviano)
            $imagenPngBinario = $this->convertirTiffAPngBinario($imagenTiffOriginal, $numeroGuia);
        }

        // Preparar datos
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

            //  Guardamos PNG binario (mucho más liviano)
            'imagen_png_binario' => $imagenPngBinario,
        ];

        // Almacenar en BD
        try {
            $trackingRecord = TrackingServientrega::updateOrCreate($identificador, $datos);

            Log::info(' Guía procesada con optimización', [
                'numero_guia' => $numeroGuia,
                'id_registro' => $trackingRecord->id,
                'imagen_optimizada' => !empty($imagenPngBinario),
                'tamaño_imagen_kb' => !empty($imagenPngBinario) ? round(strlen($imagenPngBinario) / 1024, 2) : 0
            ]);
        } catch (\Exception $e) {
            Log::error('Error BD', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Error almacenando información de la guía');
        }

        return [
            'array' => $array,
            'trackingRecord' => $trackingRecord
        ];
    }

    // Métodos públicos (sin cambios)
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
                'numeroGuia' => $numeroGuia
            ]);
        } catch (\Exception $e) {
            Log::error('Error consulta formulario', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage()
            ]);

            return back()
                ->withInput()
                ->withErrors(['Error al consultar la guía: ' . $e->getMessage()]);
        }
    }

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
            Log::error('Error consulta directa', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage()
            ]);

            return view('guia-detalle-error', [
                'mensaje' => $e->getMessage(),
                'numeroGuia' => $numeroGuia,
                'urlOrigen' => $request->get('origen', $request->header('referer', '/'))
            ]);
        }
    }
}
