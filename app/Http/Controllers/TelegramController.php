<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\Request;

class TelegramController extends Controller
{
    public function check(Request $request)
    {
        $request->validate([
            'group' => 'required|string'
        ]);

        $tg = new TelegramService();

        $result = $tg->checkGroup($request->group);

        return response()->json($result);
    }

    public function groups()
    {
        $tg = new TelegramService();

        return response()->json($tg->getGroups());
    }

    // 🔹 Keyword count
    public function count(Request $request)
    {
        $request->validate([
            'group_id' => 'required',
            'keyword' => 'required'
        ]);

        $tg = new TelegramService();

        $count = $tg->countKeyword(
            $request->group_id,
            $request->keyword
        );

        return response()->json([
            'group_id' => $request->group_id,
            'keyword' => $request->keyword,
            'count' => $count
        ]);
    }
}
