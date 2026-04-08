<?php

namespace App\Console\Commands;

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

            $groupId = (int) $group->telegram_id;

            $this->info("Scanning group: {$group->title} ({$groupId})");

            $offset_id = 0;

            do {
                try {
                    $history = $telegram->MadelineProto->messages->getHistory([
                        'peer' => $groupId,
                        'limit' => 100,
                        'offset_id' => $offset_id
                    ]);
                } catch (\Throwable $e) {
                    $this->error("Telegram error: " . $e->getMessage());
                    break;
                }

                if (!isset($history['messages']) || empty($history['messages'])) {
                    break;
                }

                $messages = $history['messages'];

                foreach ($messages as $message) {

                    if (!isset($message['message'])) continue;

                    $text = $message['message'];

                    // 🔥 barcha zaproslarni topamiz
                    preg_match_all('/\b[A-Z]{3}\d+\b/', $text, $matches);

                    foreach ($matches[0] as $customId) {

                        $query = Query::where('custom_id', $customId)
                            ->where('is_finished', false)
                            ->first();

                        if ($query) {
                            $query->increment('count');
                            $totalMatched++;

                            $this->line("Matched: {$customId}");
                        }
                    }
                }

                $offset_id = end($messages)['id'];

                sleep(1); // ⚠️ rate limit

            } while (true);

            $this->info("Finished group: {$group->title}");
        }

        $this->info("Done all groups!");
        $this->info("Total matched: {$totalMatched}");
    }
}
