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

    // 🔁 STATUS mapping (kerak bo‘lsa kengaytirasan)
    $statusMap = [
        'Одобрен' => 15,
        'В поиске перевозчика' => 12,
    ];

    $status = $statusMap[$this->item['status']] ?? null;

    if (!$status) {
        return; // noma'lum status bo‘lsa skip
    }

    $query = Query::updateOrCreate(
        ['custom_id' => $this->item['custom_id']],
        [
            'status' => $status,
            'query_created_at' => now(), // 🔥 hozirgi vaqt
        ]
    );

    // 🔥 agar status = 17 bo‘lsa -> finished
    if ($query->status == 17 && !$query->is_finished) {
        $query->update([
            'is_finished' => true
        ]);
    }
}
}
