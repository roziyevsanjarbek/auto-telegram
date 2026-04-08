<?php

namespace App\Services;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;

class TelegramService
{
    public $MadelineProto;

    public function __construct()
    {
        $settings = new Settings;

        $settings->getAppInfo()
            ->setApiId(env('TG_API_ID'))
            ->setApiHash(env('TG_API_HASH'));

        $this->MadelineProto = new API(
            storage_path('app/session.madeline'),
            $settings
        );
    }

    public function checkGroup($group)
    {
        try {
            $this->MadelineProto->start();

            $info = $this->MadelineProto->getFullInfo($group);

            return [
                'success' => true,
                'title' => $info['Chat']['title'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getGroups()
    {
        $dialogs = $this->MadelineProto->messages->getDialogs([
            'limit' => 100
        ]);


        $groups = [];

        // chats ichidan olamiz (ENG MUHIM)
        foreach ($dialogs['chats'] as $chat) {

            // faqat group va supergroup
            if ($chat['_'] === 'channel') {

                $groups[] = [
                    'id' => -100 . $chat['id'], // 🔥 private group uchun
                    'title' => $chat['title'] ?? null
                ];
            }
        }

        return $groups;
    }

    // 🔹 2. Keyword count (PRIVATE GROUP HAM ISHLAYDI)
    public function countKeyword($groupId, $keyword)
    {
        $total = 0;
        $offset_id = 0;

        do {
            try {
                $history = $this->MadelineProto->messages->getHistory([
                    'peer' => $groupId,
                    'limit' => 100,
                    'offset_id' => $offset_id
                ]);
            } catch (\Throwable $e) {
                // xato bo‘lsa skip qilamiz
                break;
            }

            // 🔥 ENG MUHIM TEKSHIRUV
            if (!$history || !is_array($history) || !isset($history['messages'])) {
                break;
            }

            $messages = $history['messages'];

            foreach ($messages as $message) {
                if (!isset($message['message'])) continue;

                if (stripos($message['message'], $keyword) !== false) {
                    $total++;
                }
            }

            if (count($messages) > 0) {
                $offset_id = end($messages)['id'];
            }

        } while (!empty($messages));

        return $total;
    }
}
