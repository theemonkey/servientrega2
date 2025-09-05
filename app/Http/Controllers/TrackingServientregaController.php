<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\TrackingServientrega;

/**
 * ====================
 * CONTROLADOR SIMPLE
 * ====================
 *
 * Enfoque simplificado:
 * - Backend: Solo almacenar imagen original como base64
 * - Frontend: Canvas TIFF viewer con detección de formato
 * - Sin conversiones complejas en backend
 */
class TrackingServientregaController extends Controller
{
    private function limpiarValor($valor)
    {
        if (is_array($valor)) {
            return implode(', ', array_filter($valor));
        }
        return $valor ?? null;
    }

    /**
     * ========================================
     * NUEVO: DETECTAR FORMATO DE IMAGEN
     * ========================================
     */
    private function detectarFormatoImagen($base64Data)
    {
        // Decodificar para obtener los primeros bytes
        $binaryData = base64_decode($base64Data, true);
        if ($binaryData === false || strlen($binaryData) < 10) {
            return 'unknown';
        }

        // Obtener header (primeros 10 bytes)
        $header = substr($binaryData, 0, 10);
        $headerHex = bin2hex($header);

        // Detectar formato por firma de archivo (magic numbers)
        $formatos = [
            'png' => ['89504e470d0a1a0a'],
            'jpg' => ['ffd8ffe0', 'ffd8ffe1', 'ffd8ffe2', 'ffd8ffe3', 'ffd8ffe8'],
            'gif' => ['474946383761', '474946383961'],
            'bmp' => ['424d'],
            'tiff' => ['49492a00', '4d4d002a'], // TIFF little-endian y big-endian
            'pdf' => ['255044462d'],
        ];

        foreach ($formatos as $formato => $signatures) {
            foreach ($signatures as $signature) {
                if (strpos(strtolower($headerHex), strtolower($signature)) === 0) {
                    Log::info(' FORMATO DETECTADO', [
                        'formato' => $formato,
                        'signature' => $signature,
                        'header_hex' => substr($headerHex, 0, 16)
                    ]);
                    return $formato;
                }
            }
        }

        Log::warning(' FORMATO NO RECONOCIDO', [
            'header_hex' => substr($headerHex, 0, 16),
            'tamaño_datos' => strlen($binaryData)
        ]);

        return 'unknown';
    }

    /**
     * Procesar guía - VERSIÓN SIMPLIFICADA
     */
    private function procesarGuia($numeroGuia, $logAcceso = false)
    {
        if (!preg_match('/^[0-9]+$/', $numeroGuia)) {
            throw new \Exception('Formato de guía inválido: debe contener solo números');
        }

        if ($logAcceso) {
            Log::info('🔍 ACCESO DIRECTO A GUÍA', [
                'numero_guia' => $numeroGuia
            ]);
        }

        // Consultar API
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
                'codigo_respuesta' => $response->status()
            ]);
        } catch (\Exception $e) {
            Log::error(' ERROR CONSULTANDO API', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        // Procesar XML
        try {
            $xml = simplexml_load_string($response->body());
            if ($xml === false) {
                throw new \Exception('Respuesta XML inválida del servicio');
            }
            $array = json_decode(json_encode($xml), true);
        } catch (\Exception $e) {
            Log::error(' ERROR PROCESANDO XML', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Error procesando respuesta de la API');
        }

        // Normalizar movimientos
        $movimientos = $array['Mov']['InformacionMov'] ?? [];
        if (!is_array($movimientos)) {
            $movimientos = [$movimientos];
        }

        /**
         * =====================================
         * SIMPLIFICADO: SOLO ALMACENAR BASE64 ORIGINAL
         * =====================================
         */
        $imagenBase64Original = null;
        $formatoDetectado = 'unknown';

        if (isset($array['Imagen']) && !empty($array['Imagen'])) {

            // PENDIENTE:
            //Descargar imagen y almacenar localmente
            // hacer una funcion que me devuelva la ruta relativa de la imagen
            //Luego guardarla en la db

            $imagenTiffOriginal = is_array($array['Imagen'])
                ? $array['Imagen'][0]
                : $array['Imagen'];

            // Detectar formato real
            $formatoDetectado = $this->detectarFormatoImagen($imagenTiffOriginal);

            // Almacenar imagen original como base64
            $imagenBase64Original = $imagenTiffOriginal;  //En esta parte pendiente poner la ruta relativa de la imagen
        }

        // Preparar datos para BD
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
            // ALMACENAR IMAGEN ORIGINAL COMO BASE64
            'imagen_base64' => $imagenBase64Original,
        ];

        // Almacenar en BD
        try {
            $trackingRecord = TrackingServientrega::updateOrCreate($identificador, $datos);

            Log::info(' GUÍA PROCESADA SIMPLIFICADA', [
                'numero_guia' => $numeroGuia,
                'id_registro' => $trackingRecord->id,
                'imagen_guardada' => !empty($imagenBase64Original),
                'formato_detectado' => $formatoDetectado,
                'tamaño_imagen_kb' => !empty($imagenBase64Original) ? round(strlen($imagenBase64Original) / 1024, 2) : 0
            ]);
        } catch (\Exception $e) {
            Log::error(' ERROR ALMACENANDO EN BD', [
                'numero_guia' => $numeroGuia,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Error almacenando información de la guía');
        }

        return [
            'array' => $array,
            'trackingRecord' => $trackingRecord,
            'imagenOriginalBase64' => $imagenBase64Original, // Para canvas
            'formatoDetectado' => $formatoDetectado
        ];
    }

    // Métodos existentes actualizados
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
                'imagenOriginalBase64' => $resultado['imagenOriginalBase64'] // ✅ Para botón canvas
            ]);
        } catch (\Exception $e) {
            Log::error(' ERROR EN CONSULTA DESDE FORMULARIO', [
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
                'urlOrigen' => $origen,
                'imagenOriginalBase64' => $resultado['imagenOriginalBase64'] // ✅ Para botón canvas
            ]);
        } catch (\Exception $e) {
            Log::error(' ERROR EN CONSULTA DIRECTA', [
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
