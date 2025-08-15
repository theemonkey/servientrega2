<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Guia;
use SoapClient;

class GuiaController extends Controller
{
    private $wsdl = 'https://web.servientrega.com/GeneracionGuias.asmx?WSDL';

    public function crearGuia(Request $request)
    {
        $client = new SoapClient($this->wsdl, ['trace' => 1]);

        $params = [
            'IdCliente' => env('SERVIENTREGA_ID_CLIENTE'),
            'NombreRemitente' => $request->remitente_nombre,
            'DireccionRemitente' => $request->remitente_direccion,
            'TelefonoRemitente' => $request->telefono_remitente,
            'NombreDestinatario' => $request->destinatario_nombre,
            'DireccionDestinatario' => $request->destinatario_direccion,
            'TelefonoDestinatario' => $request->telefono_destinatario,
            'ValorDeclarado' => $request->valor_declarado,
            'NumeroPiezas' => $request->numero_piezas
            //pendiente mas campos
        ];

        $result = $client->__soapCall('CargarGuia', [$params]);

        if (isset($result->CargarGuiaResult)) {
            $guia = Guia::create([
                'numero_guia' => $result->CargarGuiaResult->NumeroGuia,
                'fecha_envio' => now(),
                'numero_piezas' => $request->numero_piezas,
                'remitente_nombre' => $request->remitente_nombre,
                'remitente_direccion' => $request->remitente_direccion,
                'destinatario_nombre' => $request->destinatario_nombre,
                'destinatario_direccion' => $request->destinatario_direccion,
                'estado_actual_id' => null,
                'ciudad_remitente_id' => $request->ciudad_remitente_id,
                'ciudad_destino_id' => $request->ciudad_destino_id,
                'fecha_probable_entrega' => null,
                'regimen' => null
            ]);

            return response()->json($guia);
        }

        return response()->json(['error' => 'No se pudo crear la guía'], 500);
    }
}
