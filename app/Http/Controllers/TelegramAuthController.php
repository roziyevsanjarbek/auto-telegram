<?php

namespace App\Http\Controllers;

use App\Services\TelegramAuthService;
use danog\MadelineProto\API;
use Illuminate\Http\Request;

class TelegramAuthController extends Controller
{


    public function sendCode(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:255',
        ]);

        $tg = new TelegramAuthService();
        $tg->sendCode($request->phone);

        return response()->json([
            'success' => true,
            'message' => 'Code sent',
        ]);

    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:255',
            'code' => 'required|string|max:255',
        ]);

        $tg = new TelegramAuthService();
        $result = $tg->completeLogin($request->code);

        return response()->json([
            'success' => true,
            'message' => 'Code verified',
            'user' => $result,
        ]);
    }


}
