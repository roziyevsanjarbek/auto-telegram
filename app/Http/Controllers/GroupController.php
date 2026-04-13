<?php

namespace App\Http\Controllers;

use App\Models\Group;
use danog\MadelineProto\API;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Settings;
use Illuminate\Http\Request;
use App\Exports\GroupIdExport;
use Maatwebsite\Excel\Facades\Excel;

class GroupController extends Controller
{


    /**
     * @throws Exception
     */

    public function store(Request $request)
    {
        $links = $request->input('links', []);

        // 🔥 Madeline settings
        $settings = new Settings;
        $settings->getAppInfo()
            ->setApiId(env('TG_API_ID'))
            ->setApiHash(env('TG_API_HASH'));

        // 🔥 Session yaratish
        $MadelineProto = new API(
            storage_path('app/session.madeline'),
            $settings
        );

        // 🔥 Login (1-marta code so‘raydi)
        $MadelineProto->start();

        $result = [];

        foreach ($links as $link) {
            try {
                $link = trim($link);

                if (str_contains($link, 't.me/+')) {
                    // 🔐 PRIVATE GROUP
                    $hash = str_replace('https://t.me/+', '', $link);

                    $response = $MadelineProto->messages->importChatInvite([
                        'hash' => $hash,
                    ]);

                    $chat = $response['chats'][0] ?? null;
                    $groupId = $chat['id'] ?? null;
                    $title = $chat['title'] ?? null;

                } else {
                    // 🌐 PUBLIC GROUP
                    $username = str_replace('https://t.me/', '', $link);

                    $info = $MadelineProto->getFullInfo($username);

                    $groupId = $info['Chat']['id'] ?? null;
                    $title = $info['Chat']['title'] ?? null;
                }

                // 💾 DB ga yozish
                Group::updateOrCreate(
                    ['link' => $link],
                    [
                        'title' => $title,
                        'telegram_id' => $groupId,
                        'status' => 'active'
                    ]
                );

                $result[] = [
                    'link' => $link,
                    'group_id' => $groupId,
                    'status' => 'success'
                ];

                sleep(1); // ⚠️ rate limitdan qochish

            } catch (\Throwable $e) {

                Group::updateOrCreate(
                    ['link' => $link],
                    [
                        'status' => 'error'
                    ]
                );

                $result[] = [
                    'link' => $link,
                    'group_id' => null,
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'message' => 'Groups processed',
            'data' => $result
        ]);
    }
}
