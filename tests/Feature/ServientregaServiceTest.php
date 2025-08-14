<?php

namespace Tests\Feature;

use App\Services\ServientregaService;
use Tests\TestCase;

class ServientregaServiceTest extends TestCase
{
    public function test_track_shipment()
    {
        $service = new ServientregaService();
        $response = $service->trackShipment('3047885568');
        
        $this->assertArrayHasKey('Num_Guia', $response);
    }

    public function test_get_cities()
    {
        $service = new ServientregaService();
        $response = $service->getDistributionCities();
        
        $this->assertIsArray($response);
    }
}