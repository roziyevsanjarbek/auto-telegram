<?php
declare(strict_types=1);

namespace App\Telegram;

use danog\MadelineProto\EventHandler;
use App\Models\Query;
use App\Models\Group;
use App\Models\QueryMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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

        $message   = $msg['message'] ?? null;
        $messageId = $msg['id'] ?? null;
        $peer      = $msg['peer_id'] ?? null;

        if (!$message || !$messageId || !$peer) {
            return;
        }

        $telegramGroupId = $this->resolvePeerId($peer);
        if (!$telegramGroupId) return;

        // 🔥 group cache (DB ni asraydi)
        $group = Cache::remember("group_{$telegramGroupId}", 3600, function () use ($peer, $telegramGroupId) {
            return $this->createOrGetGroup($peer, $telegramGroupId);
        });

        // 🔥 regex (case-insensitive)
        preg_match_all('/\b[a-zA-Z]{3}\d+\b/i', $message, $matches);

        if (empty($matches[0])) return;

        // 🔥 querylarni oldindan olish (N+1 muammoni yo‘q qiladi)
        $queries = Query::whereIn('custom_id', $matches[0])
            ->where('is_finished', false)
            ->get()
            ->keyBy('custom_id');

        foreach ($matches[0] as $customId) {

            if (!isset($queries[$customId])) continue;

            $query = $queries[$customId];

            // 🔥 duplicate check (tezroq)
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

            Log::info("MATCHED: {$customId}");
        }
    }

    /**
     * 🔥 Peer → Telegram ID
     */
    private function resolvePeerId($peer): ?int
    {
        if (!is_array($peer) || !isset($peer['_'])) {
            return null;
        }

        return match ($peer['_']) {
            'peerChannel' => (int) ('-100' . $peer['channel_id']),
            'peerChat'    => $peer['chat_id'],
            default       => null,
        };
    }

    /**
     * 🔥 Group create yoki olish
     */
    private function createOrGetGroup($peer, int $telegramGroupId): Group
    {
        $name = 'Unknown';
        $link = null;

        try {
            $info = $this->getInfo($peer);

            $name = $info['title'] ?? 'Unknown';

            if (!empty($info['username'])) {
                $link = 'https://t.me/' . $info['username'];
            }
        } catch (\Throwable $e) {
            Log::warning('Group info error: ' . $e->getMessage());
        }

        return Group::firstOrCreate(
            ['telegram_id' => $telegramGroupId],
            [
                'title' => $name,
                'link'  => $link
            ]
        );
    }
}
