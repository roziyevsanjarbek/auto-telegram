<?php

namespace App\Console\Commands;

use App\Models\Query;
use App\Services\TelegramService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:test-telegram-count')]
#[Description('Command description')]
class TestTelegramCount extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $telegram = new TelegramService();

        // 1 ta group
        $groupId = -1003719419879; // o'zingnikini qo'y

        // 1 ta zapros
        $query = Query::first();

        if (!$query) {
            $this->error('No query found');
            return;
        }

        $count = $telegram->countKeyword($groupId, $query->custom_id);

        $this->info("Keyword: {$query->custom_id}");
        $this->info("Count: {$count}");

        // DB ga yozamiz
        $query->update([
            'count' => $count
        ]);
    }
}
