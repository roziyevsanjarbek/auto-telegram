<?php
declare(strict_types=1);

namespace App\Telegram;

use danog\MadelineProto\EventHandler;
use App\Models\Query;
use App\Models\Group;
use App\Models\QueryMessage;

class MyEventHandler extends EventHandler
{
    // PRIVATE (optional)
    public function onUpdateNewMessage($update): void
    {
        $this->handleMessage($update);
    }

    // GROUP / CHANNEL (ENG MUHIM)
    public function onUpdateNewChannelMessage($update): void
    {
        $this->handleMessage($update);
    }

    private function handleMessage($update): void
    {
        $msg = $update['message'] ?? null;
        if (!$msg) return;

        $message   = $msg['message'] ?? null;
        $messageId = $msg['id'] ?? null;
        $peer      = $msg['peer_id'] ?? null;

        $telegramGroupId = null;

        if (is_int($peer)) {
            $telegramGroupId = $peer;
        } elseif (is_array($peer) && isset($peer['_']) && $peer['_'] === 'peerChannel') {
            $telegramGroupId = '-100' . $peer['channel_id'];
        }

        if (!$message || !$messageId || !$telegramGroupId) {
            \Log::info('DEBUG SKIP', compact('message', 'messageId', 'peer'));
            return;
        }

        $name = 'Unknown';

        try {
            $fullInfo = $this->getInfo($peer);
            $name = $fullInfo['title'] ?? 'Unknown';
        } catch (\Throwable $e) {
            \Log::warning('Group info error: ' . $e->getMessage());
        }

        $group = Group::firstOrCreate(
            ['telegram_id' => $telegramGroupId],
            ['title' => $name]
        );

        preg_match_all('/\b[A-Z]{3}\d+\b/', $message, $matches);

        foreach ($matches[0] as $customId) {

            $query = Query::where('custom_id', $customId)
                ->where('is_finished', false)
                ->first();

            if (!$query) continue;

            $exists = QueryMessage::where([
                'query_id' => $query->id,
                'group_id' => $group->id,
                'message_id' => $messageId
            ])->exists();

            if ($exists) continue;

            QueryMessage::create([
                'query_id' => $query->id,
                'group_id' => $group->id,
                'message_id' => $messageId
            ]);

            $query->increment('count');

            \Log::info("✅ MATCHED: {$customId}");
        }
    }
}
