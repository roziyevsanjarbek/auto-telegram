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
            $page = 1;
            do {
                $response = Http::withoutVerifying()
                    ->timeout(15)
                    ->withToken(config('services.api.token')) // ?? space olib tashla
                    ->acceptJson()
                    ->get('https://crm.zanjeer.uz/api/v1/queries', [
                        'sort' => '-id',
                        'per_page' => 500,
                        'page' => $page,
                    ]);

                if (!$response->ok()) {
                    $this->error('API error');
                    return;
                }

                $data = $response->json()['data'];
                $items = $data['data'] ?? [];
                $lastPage = $data['last_page'] ?? 1;

                foreach ($items as $item) {
                    ProcessQueryJob::dispatch($item);
                }

                $this->info("Page {$page} processed." . ' Total: ' . count($items));

                $page++;

            } while ($page <= $lastPage);

        $this->info("All queries processed.");

        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }
}
