<?php

namespace App\Jobs;

use App\Models\Query;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

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
        if(empty($this->item['custom_id'])) {
            return;
        }

        Query::query()->updateOrCreate(
            ['custom_id' => $this->item['custom_id']],
            [
                'status' => $this->item['query_status_id'],
                'query_created_at' => Carbon::parse($this->item['created_at'])->format('Y-m-d H:i:s'),
            ]
        );
    }
}
