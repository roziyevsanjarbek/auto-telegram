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

    // 🔥 FIX: caption ham qo‘shildi
    $message   = $msg['message'] ?? $msg['media']['caption'] ?? null;
    $messageId = $msg['id'] ?? null;
    $peer      = $msg['peer_id'] ?? null;

    \Log::info('RAW MESSAGE', $msg);

   $telegramGroupId = null;

    if (is_int($peer)) {
        $telegramGroupId = $peer;
    } elseif (is_array($peer)) {
        if ($peer['_'] === 'peerChannel') {
            $telegramGroupId = (int) ('-100' . $peer['channel_id']);
        } elseif ($peer['_'] === 'peerChat') {
            $telegramGroupId = $peer['chat_id'];
        }
    }

    if (!$message || !$messageId || !$telegramGroupId) {
        \Log::info('DEBUG SKIP', compact('message', 'messageId', 'peer'));
        return;
    }

    \Log::info('MESSAGE TEXT', ['text' => $message]);

    $group = Group::firstOrCreate(
        ['telegram_id' => $telegramGroupId],
        ['title' => 'Unknown']
    );

    // 🔥 FIX: to‘g‘ri regex
    preg_match_all('/[A-Z]{3}\d{5}/', $message, $matches);

    \Log::info('MATCHES', $matches[0]);

    foreach ($matches[0] as $customId) {

        $query = Query::where('custom_id', $customId)
            ->where('is_finished', false)
            ->first();

        if (!$query) {
            \Log::info("NOT FOUND IN DB: {$customId}");
            continue;
        }

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
