<?php

namespace App\Console\Commands;

use App\Services\ServientregaService;
use Illuminate\Console\Command;

class ServientregaTrackCommand extends Command
{
    protected $signature = 'servientrega:track {trackingNumber?}';
    protected $description = 'Track a Servientrega shipment';

    public function handle()
    {
        $trackingNumber = $this->argument('trackingNumber');
        $service = new ServientregaService();

        $this->info("Tracking shipment: $trackingNumber");
        
        $response = $service->track($trackingNumber);

        if (isset($response['error'])) {
            $this->error("Error: {$response['error']}");
            return;
        }

        $this->info("Response:");
        $this->line(json_encode($response, JSON_PRETTY_PRINT));
    }
}