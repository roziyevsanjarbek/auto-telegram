<?php

namespace App\Console\Commands;

use App\Jobs\ProcessQueryJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncQueries extends Command
{
    protected $signature = 'queries:sync';
    protected $description = 'Sync queries from external API';

    public function handle()
{
    try {
        $response = Http::withoutVerifying()
            ->timeout(15)
            ->acceptJson()
            ->get('https://crm.zanjeer.uz/api/v1/queries/ids');

        if (!$response->ok()) {
            $this->error('API error');
            return;
        }

        $items = $response->json()['data'] ?? [];

        foreach ($items as $item) {
            ProcessQueryJob::dispatch($item);
        }

        $this->info("Total processed: " . count($items));

    } catch (\Throwable $e) {
        $this->error($e->getMessage());
    }
}
}
