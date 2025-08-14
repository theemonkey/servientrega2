<?php

namespace App\Jobs;

use App\Models\ServientregaTracking;
use App\Services\ServientregaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessServientregaShipment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $shipmentData;
    protected $type;

    public function __construct(array $shipmentData, string $type = 'premier')
    {
        $this->shipmentData = $shipmentData;
        $this->type = $type;
    }

    public function handle()
    {
        $service = new ServientregaService();
        $response = $service->createShipment($this->shipmentData, $this->type);

        ServientregaTracking::create([
            'type' => 'shipment',
            'reference' => $response['Num_Guia'] ?? null,
            'request_data' => json_encode($this->shipmentData),
            'response_data' => $response
        ]);
    }
}