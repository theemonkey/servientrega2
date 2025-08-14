<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class ServientregaService
{
    protected $baseUrl;
    protected $authToken;

    public function __construct()
    {
        $this->baseUrl = config('services.servientrega.api_url');
        $this->authToken = $this->authenticate();
    }

    /**
     * Realiza una petición a la API de Servientrega
     */
    protected function makeApiRequest(string $method, string $endpoint, array $data = [])
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->authToken,
                'Content-Type' => 'application/json',
            ])->{$method}($this->baseUrl . $endpoint, $data);

            return $response->json();
        } catch (Exception $e) {
            throw new Exception("API request failed: " . $e->getMessage());
        }
    }

    /**
     * Autenticación con la API de Servientrega
     */
    protected function authenticate()
    {
        try {
            $response = Http::post($this->baseUrl . '/auth', [
                'username' => config('services.servientrega.username'),
                'password' => config('services.servientrega.password')
            ]);

            return $response->json()['token'];
        } catch (Exception $e) {
            throw new Exception("Authentication failed: " . $e->getMessage());
        }
    }

    /**
     * Rastreo de envíos
     */
    public function track($trackingNumber)
    {
        try {
            $response = $this->makeApiRequest('GET', "/tracking/$trackingNumber");
            return $response;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}