<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; 
use App\Models\TrackingServientrega;

class TrackingServientregaController extends Controller
{
    // 🔹 Helper para limpiar valores antes de guardar
    private function limpiarValor($valor)
    {
        if (is_array($valor)) {
            return implode(', ', array_filter($valor)); // Convierte array a string
        }
        return $valor ?? null; // Si es null, devuelve null
    }

    public function consultarGuia(Request $request)
    {
        $numeroGuia = $request->input('numero_guia');

        // Llamada GET al endpoint de Servientrega
        $response = Http::get("https://wssismilenio.servientrega.com/wsrastreoenvios/wsrastreoenvios.asmx/ConsultarGuiaExterno", [
            'NumeroGuia' => $numeroGuia
        ]);
        
        if ($response->successful()) {
            // Parsear XML a array
            $xml = simplexml_load_string($response->body());
            $array = json_decode(json_encode($xml), true); 

            // Navegar hasta los movimientos
            $movimientos = $array['Mov']['InformacionMov'] ?? [];
            if (!is_array($movimientos)) {
                $movimientos = [$movimientos]; // Si solo hay 1 movimiento
            }

            // Tomar el último movimiento como estado actual
            $ultimoMovimiento = end($movimientos);

            $estado = $ultimoMovimiento['NomMov'] ?? null;
            $ciudad = $ultimoMovimiento['DesMov'] ?? null;
            $fecha = isset($ultimoMovimiento['FecMov']) 
                ? date('Y-m-d', strtotime($ultimoMovimiento['FecMov'])) 
                : null;

            // Identificador para updateOrCreate
            $identificador = [
                'numero_guia' => $numeroGuia,
            ];

            // Normalizar y preparar datos
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
                'movimientos' => $movimientos, // Guardamos siempre en JSON
            ];

            // Guardar o actualizar
            TrackingServientrega::updateOrCreate($identificador, $datos);

            return view('resultados', [
                'respuesta' => $array,
            ]);
        }

        return back()->withErrors(['Error al consultar la guía']);
    }
}
