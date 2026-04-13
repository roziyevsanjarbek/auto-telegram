<?php
declare(strict_types=1);

namespace App\Telegram;

use danog\MadelineProto\EventHandler;
use App\Models\Query;
use App\Models\Group;
use App\Models\QueryMessage;

class MyEventHandler extends EventHandler
{
    public function onUpdateNewMessage($update): void
    {
        $this->handleMessage($update);
    }

    public function onUpdateNewChannelMessage($update): void
    {
        $this->handleMessage($update);
    }

    private function handleMessage($update): void
    {
        $msg = $update['message'] ?? null;
        if (!$msg) return;

        // Message (text yoki caption)
        $message   = $msg['message'] ?? $msg['media']['caption'] ?? null;
        $messageId = $msg['id'] ?? null;
        $peer      = $msg['peer_id'] ?? null;

        if (!$message || !$messageId || !$peer) {
            return;
        }

        // 🔹 Telegram group ID olish
        $telegramGroupId = $this->resolveTelegramGroupId($peer);
        if (!$telegramGroupId) return;

        $name = 'Unknown';
        $link = null;

        // 🔹 Chat info olish (optional!)
        $chat = $this->getChatInfo($peer);
        \Log::info('Chat info: ' . json_encode($chat));

        if ($chat && empty($chat['left'])) {
            $name     = $chat['title'] ?? 'Unknown';
            $username = $chat['username'] ?? null;
            $link     = $username ? 'https://t.me/' . $username : null;
        }

        // 🔹 Group DB ga yozish
        $group = Group::firstOrCreate(
            ['telegram_id' => $telegramGroupId],
            [
                'title' => $name,
                'link'  => $link
            ]
        );

        // 🔹 ID larni topish (ENG MUHIM QISM)
        preg_match_all('/[A-Z]{3}\d{5}/', $message, $matches);

        foreach ($matches[0] as $customId) {

            $query = Query::where('custom_id', $customId)
                ->where('is_finished', false)
                ->first();

            if (!$query) continue;

            // ❗ duplicate oldini olish (bonus)
            $exists = QueryMessage::where('query_id', $query->id)
                ->where('message_id', $messageId)
                ->exists();

            if ($exists) continue;

            QueryMessage::create([
                'query_id'   => $query->id,
                'group_id'   => $group->id,
                'message_id' => $messageId
            ]);

            $query->increment('count');
        }
    }

    /**
     * Telegram group ID ni aniqlash
     */
    private function resolveTelegramGroupId($peer): ?int
    {
        if (is_int($peer)) {
            return $peer;
        }

        if (is_array($peer)) {
            return match ($peer['_']) {
                'peerChannel' => (int) ('-100' . $peer['channel_id']),
                'peerChat'    => $peer['chat_id'],
                default       => null,
            };
        }

        return null;
    }

    /**
     * Chat info olish (filter bilan)
     */
    private function getChatInfo($peer): ?array
    {
        try {
            $info = $this->getInfo($peer);
            return $info['Chat'] ?? null;
        } catch (\Throwable $e) {
            \Log::warning('Chat info error: ' . $e->getMessage());
            return null;
        }
    }
}
