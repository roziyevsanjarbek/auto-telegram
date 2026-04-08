<?php

namespace App\Console\Commands;

use App\Jobs\ScanGroupJob;
use Illuminate\Console\Command;
use App\Models\Query;
use App\Models\Group;
use App\Services\TelegramService;

class ScanOneGroup extends Command
{
        protected $signature = 'scan:groups';
    protected $description = 'Scan all telegram groups and count queries';

    public function handle()
    {
        $telegram = new TelegramService();

        // 🔥 reset faqat 1 marta
        Query::where('is_finished', false)->update(['count' => 0]);

        // 🔥 barcha active group
        $groups = Group::where('status', true)->get();

        $this->info("Total groups: " . $groups->count());

        $totalMatched = 0;

        foreach ($groups as $group) {
            ScanGroupJob::dispatch($group);
        }

        $this->info("Done all groups!");
        $this->info("Total matched: {$totalMatched}");
    }
}
