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

    // Convertir TIFF base64 a PNG base64
    private function convertirTiffAPng($tiffBase64)
    {
        try {
            if (empty($tiffBase64)) {
                throw new \Exception("Base64 image string is empty");
            }

            $tiffBinario = base64_decode($tiffBase64);
            if ($tiffBinario === false) {
                throw new \Exception("Invalid base64 string");
            }
            
            $tempTiffPath = tempnam(sys_get_temp_dir(), 'tiff_') . '.tiff';
            file_put_contents($tempTiffPath, $tiffBinario);
            
            try {
                $manager = new ImageManager(new Driver());
                $image = $manager->read($tempTiffPath);
                $pngBinario = $image->toPng();
            } catch (\Exception $e) {
                throw new \Exception("Error procesando imagen: " . $e->getMessage());
            }
            
            if(file_exists($tempTiffPath)) {
                unlink($tempTiffPath);
            }

            return base64_encode($pngBinario);
            
        } catch (\Exception $e) {
            Log::error("Error convirtiendo TIFF a PNG: " . $e->getMessage());
            return null;
        }
    }

    // ===>> MÉTODO PRINCIPAL - Procesa cualquier guía ===>>
    private function procesarGuia($numeroGuia, $logAcceso = false)
    {
        // Validar formato de guía
        if (!preg_match('/^[0-9]+$/', $numeroGuia)) {
            throw new \Exception('Formato de guía inválido');
        }

        // Llamar a la API
        $response = Http::get("https://wssismilenio.servientrega.com/wsrastreoenvios/wsrastreoenvios.asmx/ConsultarGuiaExterno", [
            'NumeroGuia' => $numeroGuia
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('Guía no encontrada en el sistema');
        }

        // Procesar respuesta XML
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
            $imagenBase64Original = preg_replace('/\s+/', '', $imagenBase64Original);
            $imagenPngBase64 = $this->convertirTiffAPng($imagenBase64Original);
        }

        // Preparar datos para guardar
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

        // Log opcional para acceso directo
        if ($logAcceso) {
            Log::info('Acceso directo a guía', [
                'guia' => $numeroGuia,
                'ip' => request()->ip(),
                'timestamp' => now()
            ]);
        }

        return [
            'array' => $array,
            'trackingRecord' => $trackingRecord
        ];
    }

    // ===>> Consulta de guía vía formulario ===>>
    public function consultarGuia(Request $request)
    {
        $request->validate([
            'numero_guia' => 'required|numeric',
        ]);

        $numeroGuia = $request->input('numero_guia');

        try {
            $resultado = $this->procesarGuia($numeroGuia);

            return view('resultados', [
                'respuesta' => $resultado['array'],
                'trackingRecord' => $resultado['trackingRecord'],
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['Error al consultar la guía: ' . $e->getMessage()]);
        }
    }

    // ===>> Consulta de guía vía directa ===>>
    public function verGuia($numeroGuia, Request $request)
    {
        try {
            $resultado = $this->procesarGuia($numeroGuia, true);
            
            // Capturar origen para navegación
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
}