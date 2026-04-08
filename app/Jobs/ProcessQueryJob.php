<?php

namespace App\Jobs;

use App\Models\Query;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class ProcessQueryJob implements ShouldQueue
{
    use Queueable, Dispatchable;

    public $item;

    /**
     * Create a new job instance.
     */
    public function __construct($item)
    {
        $this->item = $item;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->item['custom_id'])) {
            return;
        }

        $query = Query::query()->updateOrCreate(
            ['custom_id' => $this->item['custom_id']],
            [
                'status' => $this->item['query_status_id'],
                'query_created_at' => Carbon::parse($this->item['created_at'])->format('Y-m-d H:i:s'),
            ]
        );

        // 🔥 STATUS = 17 (atmen)
        if ($query->status == 17 && !$query->is_finished) {

            try {
                Http::timeout(10)->post('https://your-api-url', [
                    'id' => $query->custom_id,
                    'count' => $query->count,
                ]);

                // ✅ yuborildi → finish qilamiz
                $query->update([
                    'is_finished' => true
                ]);

            } catch (\Throwable $e) {
                \Log::error("API send failed: " . $e->getMessage());
            }
        }
    }
}
