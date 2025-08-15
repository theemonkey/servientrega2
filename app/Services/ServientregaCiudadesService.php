<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ServientregaCiudadesService
{
    private string $baseLogin = 'https://apim-servientrega-prd.azure-api.net/cds-reclamo-oficina/api/auth/Login';
    private string $consultaCiudadesUrl = 'https://apim-servientrega-prd.azure-api.net/ciudades-distribucion/api/consultaCiudadesDistribucion';
    private string $reclamoOficinaUrl   = 'https://apim-servientrega-prd.azure-api.net/cds-reclamo-oficina/api/consultaCDSReclamoOficina';

    public function login(): string
    {
        $resp = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => config('services.servientrega.apim_key'),
        ])->post($this->baseLogin, [
            'username' => config('services.servientrega.ciudades_login'),
            'password' => config('services.servientrega.ciudades_password'),
        ]);

        $resp->throw();
        // usualmente retorna { token: "..." }
        return data_get($resp->json(), 'token');
    }

    public function consultaCiudades(string $token, array $payload)
    {
        return Http::withToken($token)->withHeaders([
            'Ocp-Apim-Subscription-Key' => config('services.servientrega.apim_key'),
        ])->post($this->consultaCiudadesUrl, $payload)->throw()->json();
    }

    public function reclamoEnOficina(string $token, array $payload)
    {
        return Http::withToken($token)->withHeaders([
            'Ocp-Apim-Subscription-Key' => config('services.servientrega.apim_key'),
        ])->post($this->reclamoOficinaUrl, $payload)->throw()->json();
    }
}
