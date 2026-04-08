<?php

namespace App\Jobs;

use App\Models\Query;
use App\Models\Group;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

class ScanGroupJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $group;

    public function __construct(Group $group)
    {
        $this->group = $group;
    }

    public function handle(): void
    {
        $telegram = new TelegramService();

        $groupId = (int) $this->group->telegram_id;

        $offset_id = 0;

        do {
            try {
                $history = $telegram->MadelineProto->messages->getHistory([
                    'peer' => $groupId,
                    'limit' => 100,
                    'offset_id' => $offset_id
                ]);
            } catch (\Throwable $e) {
                \Log::error("Telegram error: " . $e->getMessage());
                break;
            }

            if (!isset($history['messages']) || empty($history['messages'])) {
                break;
            }

            $messages = $history['messages'];

            foreach ($messages as $message) {

                if (!isset($message['message'])) continue;

                $text = $message['message'];

                preg_match_all('/\b[A-Z]{3}\d+\b/', $text, $matches);

                foreach ($matches[0] as $customId) {

                    Query::where('custom_id', $customId)
                        ->where('is_finished', false)
                        ->increment('count');
                }
            }

            $offset_id = end($messages)['id'];

            sleep(1);

        } while (true);

        \Log::info("Finished group: {$this->group->title}");
    }
}
