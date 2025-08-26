<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; 
use Illuminate\Support\Facades\Log;
use App\Models\TrackingServientrega;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver; // o use Intervention\Image\Drivers\Imagick\Driver;

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
            // Validar que la imagen no esté vacía
            if (empty($tiffBase64)) {
                throw new \Exception("Base64 image string is empty");
            }

            // Decodificar base64 a binario
            $tiffBinario = base64_decode($tiffBase64);
            if ($tiffBinario === false) {
                throw new \Exception("Invalid base64 string");
            }
            
            // Crear un archivo temporal
            $tempTiffPath = tempnam(sys_get_temp_dir(), 'tiff_') . '.tiff';
            file_put_contents($tempTiffPath, $tiffBinario);
            
            // Convertir TIFF a PNG usando Intervention Image v3
            try {
                // Crear manager con driver GD
                $manager = new ImageManager(new Driver());
                
                // Leer la imagen
                $image = $manager->read($tempTiffPath);
                
                // Convertir a PNG
                $pngBinario = $image->toPng();
                
            } catch (\Exception $e) {
                throw new \Exception("Error procesando imagen: " . $e->getMessage());
            }
            
            // Limpiar archivo temporal
            if(file_exists($tempTiffPath)) {
                unlink($tempTiffPath);
            }

            // Convertir PNG a base64
            return base64_encode($pngBinario);
            
        } catch (\Exception $e) {
            Log::error("Error convirtiendo TIFF a PNG: " . $e->getMessage());
            return null;
        }
    }

    public function consultarGuia(Request $request)
    {
        $request->validate([
            'numero_guia' => 'required|numeric',
        ]);

        $numeroGuia = $request->input('numero_guia');

        $response = Http::get("https://wssismilenio.servientrega.com/wsrastreoenvios/wsrastreoenvios.asmx/ConsultarGuiaExterno", [
            'NumeroGuia' => $numeroGuia
        ]);
        
        if ($response->successful()) {
            $xml = simplexml_load_string($response->body());
            $array = json_decode(json_encode($xml), true); 

            $movimientos = $array['Mov']['InformacionMov'] ?? [];
            if (!is_array($movimientos)) {
                $movimientos = [$movimientos];
            }

            $ultimoMovimiento = end($movimientos);
            $estado = $ultimoMovimiento['NomMov'] ?? null;
            $ciudad = $ultimoMovimiento['DesMov'] ?? null;
            $fecha = isset($ultimoMovimiento['FecMov']) 
                ? date('Y-m-d', strtotime($ultimoMovimiento['FecMov'])) 
                : null;

            // Capturar y convertir la imagen
            $imagenBase64Original = null;
            $imagenPngBase64 = null;
            
            if (isset($array['Imagen']) && !empty($array['Imagen'])) {
                $imagenBase64Original = is_array($array['Imagen']) ? $array['Imagen'][0] : $array['Imagen'];
                $imagenBase64Original = preg_replace('/\s+/', '', $imagenBase64Original);
                
                // Convertir TIFF a PNG
                $imagenPngBase64 = $this->convertirTiffAPng($imagenBase64Original);
            }

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
                'imagen_base64' => $imagenPngBase64, // Guardar PNG convertido
                'movimientos' => $movimientos, 
            ];

            $trackingRecord = TrackingServientrega::updateOrCreate($identificador, $datos);

            return view('resultados', [
                'respuesta' => $array,
                'trackingRecord' => $trackingRecord,
            ]);
        }

        return back()->withErrors(['Error al consultar la guía']);
    }
}