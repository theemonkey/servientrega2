<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\TrackingServientrega;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

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

    // MÉTODO PRINCIPAL DE CONVERSIÓN (Simplificado)
    private function convertirTiffAPng($tiffBase64)
    {
        try {
            // Limpiar base64
            $base64Limpio = preg_replace('/[^A-Za-z0-9+\/=]/', '', $tiffBase64);
            $tiffBinario = base64_decode($base64Limpio, true);

            if ($tiffBinario === false) {
                Log::warning('No se pudo decodificar base64');
                return null;
            }

            // MÉTODO 1: Imagick directo (más confiable)
            if (extension_loaded('imagick') && class_exists('Imagick')) {
                try {
                    $imagick = new \Imagick();
                    $imagick->readImageBlob($tiffBinario);
                    $imagick->setImageFormat('png');

                    $pngData = $imagick->getImageBlob();
                    $pngBase64 = base64_encode($pngData);

                    $imagick->clear();
                    $imagick->destroy();

                    Log::info('Imagen convertida con Imagick');
                    return $pngBase64;
                } catch (\Exception $e) {
                    Log::warning('Imagick falló: ' . $e->getMessage());
                }
            }

            // MÉTODO 2: Intervention Image
            try {
                if (extension_loaded('imagick')) {
                    $manager = new ImageManager(new ImagickDriver());
                } else {
                    $manager = new ImageManager(new GdDriver());
                }

                $image = $manager->read($tiffBinario);
                $pngData = $image->toPng()->toString();

                Log::info('Imagen convertida con Intervention Image');
                return base64_encode($pngData);
            } catch (\Exception $e) {
                Log::warning('Intervention Image falló: ' . $e->getMessage());
            }

            // MÉTODO 3: GD como último recurso
            if (extension_loaded('gd')) {
                try {
                    $image = imagecreatefromstring($tiffBinario);

                    if ($image !== false) {
                        ob_start();
                        imagepng($image);
                        $pngData = ob_get_contents();
                        ob_end_clean();
                        imagedestroy($image);

                        Log::info('Imagen convertida con GD');
                        return base64_encode($pngData);
                    }
                } catch (\Exception $e) {
                    Log::warning('GD falló: ' . $e->getMessage());
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error en conversión de imagen: ' . $e->getMessage());
            return null;
        }
    }

    // MÉTODO PRINCIPAL - Procesa cualquier guía
    private function procesarGuia($numeroGuia, $logAcceso = false)
    {
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

        // Procesar imagen si existe
        $imagenPngBase64 = null;
        if (isset($array['Imagen']) && !empty($array['Imagen'])) {
            $imagenBase64Original = is_array($array['Imagen']) ? $array['Imagen'][0] : $array['Imagen'];
            $imagenPngBase64 = $this->convertirTiffAPng($imagenBase64Original);
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

        $trackingRecord = TrackingServientrega::updateOrCreate($identificador, $datos);

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
            Log::error('Error en consultarGuia: ' . $e->getMessage());
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
            Log::error('Error consultando guía: ' . $e->getMessage());

            return view('guia-detalle-error', [
                'mensaje' => $e->getMessage(),
                'numeroGuia' => $numeroGuia,
                'urlOrigen' => $request->get('origen', $request->header('referer', '/'))
            ]);
        }
    }

    // OPCIONAL: Métodos de debugging (puedes eliminarlos si quieres)
    public function reprocesarImagen($numeroGuia)
    {
        try {
            $trackingRecord = TrackingServientrega::where('numero_guia', $numeroGuia)->first();

            if (!$trackingRecord) {
                return response()->json(['error' => 'Guía no encontrada']);
            }

            // Obtener imagen fresca de la API
            $response = Http::timeout(60)->get("https://wssismilenio.servientrega.com/wsrastreoenvios/wsrastreoenvios.asmx/ConsultarGuiaExterno", [
                'NumeroGuia' => $numeroGuia
            ]);

            $xml = simplexml_load_string($response->body());
            $array = json_decode(json_encode($xml), true);

            if (isset($array['Imagen']) && !empty($array['Imagen'])) {
                $imagenOriginal = is_array($array['Imagen']) ? $array['Imagen'][0] : $array['Imagen'];
                $resultado = $this->convertirTiffAPng($imagenOriginal);

                if ($resultado) {
                    $trackingRecord->imagen_base64 = $resultado;
                    $trackingRecord->save();

                    return response()->json([
                        'success' => true,
                        'mensaje' => 'Imagen reprocesada exitosamente'
                    ]);
                }
            }

            return response()->json(['error' => 'No se pudo reprocesar la imagen']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
}
