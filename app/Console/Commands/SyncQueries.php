<?php

namespace App\Console\Commands;

use App\Jobs\ProcessQueryJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Query;

class SyncQueries extends Command
{
    protected $signature = 'queries:sync';
    protected $description = 'Sync queries from external API';

    public function handle()
    {
        try {

            $page = 1;
            $allItems = [];

            do {
                $response = Http::withoutVerifying()
                    ->timeout(15)
                    ->withToken('3|i2GoJtvl8zfSaHSAN4pSy5IGRbaudqWClz0hIvPBa0f7bb68') // ?? space olib tashla
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

                // ? yig?ib boramiz
                $allItems = array_merge($allItems, $items);

                $lastPage = $data['last_page'] ?? 1;

                $page++;

            } while ($page <= $lastPage);

            // ? tekshir
            \Log::info('TOTAL ITEMS: ' . count($allItems));

            // ? queuega yuboramiz
            collect($allItems)->chunk(500)->each(function ($chunk) {
                foreach ($chunk as $item) {
                    ProcessQueryJob::dispatch($item);
                }
            });

            $this->info('Dispatched: ' . count($allItems));

        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }
}
