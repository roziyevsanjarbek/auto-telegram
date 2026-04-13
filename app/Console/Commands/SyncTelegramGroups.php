<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use App\Models\Group;

class SyncTelegramGroups extends Command
{
    protected $signature = 'telegram:sync-groups';
    protected $description = 'Sync ONLY active Telegram groups';


    public function handle()
    {
        $MadelineProto = new API(storage_path('app/session.madeline'));

        $MadelineProto->start();

        $offsetPeer = null;

        do {
            $dialogs = $MadelineProto->messages->getDialogs([
                'limit' => 100,
                'offset_peer' => $offsetPeer,
            ]);

            if (empty($dialogs['dialogs'])) {
                break;
            }

            foreach ($dialogs['dialogs'] as $dialog) {

                if (!isset($dialog['peer'])) continue;

                $peer = $dialog['peer'];

                if (($peer['_'] ?? null) !== 'peerChannel') continue;

                $info = $MadelineProto->getInfo($peer);
                $chat = $info['Chat'] ?? null;

                if (!$chat) continue;

                // 🔥 FILTER
                if (
                    empty($chat['megagroup']) ||
                    !empty($chat['left']) ||
                    !empty($chat['kicked']) ||
                    !empty($chat['deactivated']) ||
                    !empty($chat['broadcast'])
                ) {
                    continue;
                }

                $telegramId = (int) ('-100' . $chat['id']);
                $name       = $chat['title'] ?? 'Unknown';
                $username   = $chat['username'] ?? null;
                $link       = $username ? 'https://t.me/' . $username : null;

                \App\Models\Group::updateOrCreate(
                    ['telegram_id' => $telegramId],
                    [
                        'title' => $name,
                        'link'  => $link
                    ]
                );

                $this->info("Saved: {$name}");
            }

            $offsetPeer = end($dialogs['dialogs'])['peer'] ?? null;

        } while ($dialogs['dialogs']);

        // 🔴 ENG MUHIM
        $MadelineProto->stop();

        $this->info('✅ DONE');
    }
}
