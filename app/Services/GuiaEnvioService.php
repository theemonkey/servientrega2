<?php

namespace App\Services;

use App\Models\GuiaEnvio;
use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\Log;

class GuiaEnvioService
{
    private $login;
    private $password;
    private $codFacturacion;
    private $endpoint;

    public function __construct()
    {
        $this->login = config('services.servientrega.login');
        $this->password = config('services.servientrega.password');
        $this->codFacturacion = config('services.servientrega.cod_facturacion');
        $this->endpoint = 'https://developer.servientrega.com/WsSisclinetGeneraGuias/GeneracionGuias.asmx';
    }

    public function generarGuia(GuiaEnvio $guia)
    {
        try {
            $guia->update(['estado' => 'procesando']);

            // Construir el XML completo
            $xml = $this->construirXMLCompleto($guia);
            
            Log::info('XML enviado a Servientrega para guía ID ' . $guia->id, ['xml' => $xml]);

            // Realizar la llamada HTTP
            $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => '"http://tempuri.org/CargueMasivoExterno"'
            ])->withBody($xml, 'text/xml')
            ->timeout(60)
            ->post($this->endpoint);

            if ($response->successful()) {
                $responseBody = $response->body();
                Log::info('Respuesta Servientrega para guía ID ' . $guia->id, ['response' => $responseBody]);
                
                return $this->procesarRespuestaXML($guia, $responseBody);
            } else {
                throw new Exception('Error HTTP: ' . $response->status() . ' - ' . $response->body());
            }

        } catch (Exception $e) {
            Log::error('Error generando guía ID ' . $guia->id . ': ' . $e->getMessage());
            
            $guia->marcarComoError($e->getMessage());
            throw $e;
        }
    }

    private function construirXMLCompleto(GuiaEnvio $guia)
    {
        $cuerpoEnvio = $this->construirCuerpoEnvio($guia);
        
        return '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/">
    <soapenv:Header>
        <tem:AuthHeader>
            <tem:login>' . htmlspecialchars($this->login) . '</tem:login>
            <tem:pwd>' . htmlspecialchars($this->password) . '</tem:pwd>
            <tem:Id_CodFacturacion>' . htmlspecialchars($this->codFacturacion) . '</tem:Id_CodFacturacion>
        </tem:AuthHeader>
    </soapenv:Header>
    <soapenv:Body>
        <tem:CargueMasivoExterno>
            <tem:envios>
                <tem:CargueMasivoExternoDTO>
                    <tem:objEnvios>
                        <tem:EnviosExterno>
                            ' . $cuerpoEnvio . '
                        </tem:EnviosExterno>
                    </tem:objEnvios>
                </tem:CargueMasivoExternoDTO>
            </tem:envios>
        </tem:CargueMasivoExterno>
    </soapenv:Body>
</soapenv:Envelope>';
    }

    private function construirCuerpoEnvio(GuiaEnvio $guia)
    {
        $unidadesEmpaqueXML = $this->construirUnidadesEmpaqueXML($guia);
        
        return '<tem:Ide_Producto>6</tem:Ide_Producto>
            <tem:Des_FormaPago>2</tem:Des_FormaPago>
            <tem:Des_MedioTransporte>2</tem:Des_MedioTransporte>
            <tem:Num_ValorDeclaradoTotal>' . $guia->num_valor_declarado_total . '</tem:Num_ValorDeclaradoTotal>
            <tem:Des_TipoDuracionTrayecto>1</tem:Des_TipoDuracionTrayecto>
            <tem:Des_Ciudad>' . htmlspecialchars($guia->des_ciudad) . '</tem:Des_Ciudad>
            <tem:Des_Direccion>' . htmlspecialchars($guia->des_direccion) . '</tem:Des_Direccion>
            <tem:Nom_Contacto>' . htmlspecialchars($guia->nom_contacto) . '</tem:Nom_Contacto>
            <tem:Des_DiceContener>' . htmlspecialchars($guia->des_dice_contener) . '</tem:Des_DiceContener>
            <tem:Des_DepartamentoDestino>' . htmlspecialchars($guia->des_departamento_destino) . '</tem:Des_DepartamentoDestino>
            <tem:Nom_UnidadEmpaque>generico</tem:Nom_UnidadEmpaque>
            <tem:Des_UnidadLongitud>cm</tem:Des_UnidadLongitud>
            <tem:Des_UnidadPeso>kg</tem:Des_UnidadPeso>
            <tem:Des_IdArchivoOrigen>0</tem:Des_IdArchivoOrigen>
            <tem:objEnviosUnidadEmpaqueCargue>
                ' . $unidadesEmpaqueXML . '
            </tem:objEnviosUnidadEmpaqueCargue>';
    }

    private function construirUnidadesEmpaqueXML(GuiaEnvio $guia)
    {
        $unidadesXML = '';
        
        foreach ($guia->unidadesEmpaque as $unidad) {
            $unidadesXML .= '<tem:EnviosUnidadEmpaqueCargue>
                    <tem:Num_Alto>' . $unidad->num_alto . '</tem:Num_Alto>
                    <tem:Num_Ancho>' . $unidad->num_ancho . '</tem:Num_Ancho>
                    <tem:Num_Cantidad>' . $unidad->num_cantidad . '</tem:Num_Cantidad>
                    <tem:Des_DiceContener>' . htmlspecialchars($unidad->des_dice_contener) . '</tem:Des_DiceContener>
                    <tem:Des_IdArchivoOrigen>0</tem:Des_IdArchivoOrigen>
                    <tem:Num_Largo>' . $unidad->num_largo . '</tem:Num_Largo>
                    <tem:Nom_UnidadEmpaque>generico</tem:Nom_UnidadEmpaque>
                    <tem:Num_Peso>' . $unidad->num_peso . '</tem:Num_Peso>
                    <tem:Des_UnidadLongitud>cm</tem:Des_UnidadLongitud>
                    <tem:Des_UnidadPeso>kg</tem:Des_UnidadPeso>
                </tem:EnviosUnidadEmpaqueCargue>';
        }
        
        return $unidadesXML;
    }

    private function procesarRespuestaXML(GuiaEnvio $guia, $responseXML)
    {
        try {
            // Eliminar namespaces para facilitar el parsing
            $cleanXML = str_replace([
                'soap:',
                'tem:',
                'xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"',
                'xmlns:tem="http://tempuri.org/"'
            ], '', $responseXML);
            
            $xml = simplexml_load_string($cleanXML);
            
            if ($xml === false) {
                throw new Exception('No se pudo parsear la respuesta XML');
            }

            // Navegar por la estructura de respuesta
            $resultado = $xml->Body->CargueMasivoExternoResponse->CargueMasivoExternoResult ?? null;
            
            if ($resultado && (string)$resultado === 'true') {
                
                $envioData = $xml->Body->CargueMasivoExternoResponse->envios->CargueMasivoExternoDTO->objEnvios->EnviosExterno ?? null;
                
                if ($envioData && isset($envioData->Num_Guia)) {
                    // Actualizar la guía con los datos de respuesta
                    $this->actualizarGuiaConRespuesta($guia, $envioData);
                    
                    $numeroGuia = (string)$envioData->Num_Guia;
                    $guia->marcarComoGenerada($numeroGuia, [
                        'xml_response' => $responseXML,
                        'parsed_data' => json_decode(json_encode($envioData), true)
                    ]);
                    
                    return [
                        'success' => true,
                        'numero_guia' => $numeroGuia,
                        'data' => $envioData
                    ];
                }
            }
            
            // Si llegamos aquí, hubo un error
            $errorMsg = 'Respuesta inválida de la API de Servientrega';
            
            // Tratar de extraer mensaje de error específico si existe
            if (isset($xml->Body->Fault)) {
                $errorMsg = (string)$xml->Body->Fault->faultstring;
            }
            
            $guia->marcarComoError($errorMsg);
            return [
                'success' => false,
                'message' => $errorMsg,
                'raw_response' => $responseXML
            ];
            
        } catch (Exception $e) {
            Log::error('Error procesando respuesta XML: ' . $e->getMessage());
            $guia->marcarComoError('Error procesando respuesta: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error procesando respuesta de Servientrega'
            ];
        }
    }

    private function actualizarGuiaConRespuesta(GuiaEnvio $guia, $envioData)
    {
        $guia->update([
            'num_sobreporte' => (int)($envioData->Num_Sobreporte ?? 0),
            'num_sobre_caja_porte' => (int)($envioData->Num_SobreCajaPorte ?? 0),
            'des_tipo_trayecto' => (int)($envioData->Des_TipoTrayecto ?? 0),
            'ide_destinatarios' => (string)($envioData->Ide_Destinatarios ?? null),
            'ide_manifiesto' => (string)($envioData->Ide_Manifiesto ?? null),
            'num_peso_total' => (float)($envioData->Num_PesoTotal ?? 0),
            'num_volumen_total' => (float)($envioData->Num_VolumenTotal ?? 0),
            'num_bolsa_seguridad' => (int)($envioData->Num_BolsaSeguridad ?? 0),
            'num_precinto' => (int)($envioData->Num_Precinto ?? 0),
            'num_valor_liquidado' => (float)($envioData->Num_ValorLiquidado ?? 0),
            'des_tipo_guia' => (int)($envioData->Des_TipoGuia ?? 2),
            'num_vlr_sobreflete' => (float)($envioData->Num_VlrSobreflete ?? 0),
            'num_vlr_flete' => (float)($envioData->Num_VlrFlete ?? 0),
            'num_descuento' => (float)($envioData->Num_Descuento ?? 0),
            'num_peso_facturado' => (float)($envioData->Num_PesoFacturado ?? 0),
            'est_canal_mayorista' => (bool)($envioData->Est_CanalMayorista ?? false),
            'gen_cajaporte' => (bool)($envioData->Gen_Cajaporte ?? false),
            'gen_sobreporte' => (bool)($envioData->Gen_Sobreporte ?? false),
            'id_archivo_cargar' => (string)($envioData->Id_ArchivoCargar ?? null),
            'est_enviar_correo' => (bool)($envioData->Est_EnviarCorreo ?? false),
            'retorno_digital' => (int)($envioData->Retorno_Digital ?? 0)
        ]);
    }
}