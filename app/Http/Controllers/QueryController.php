<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Query;
use Illuminate\Http\Request;

class QueryController extends Controller
{
    public function index()
    {
        $queries = Query::query()
            ->where('status', 17)
            ->where('is_finished', true)
            ->select( 'custom_id', 'count')
            ->get();

        $groupCount = Group::query()->count();

        if ($queries->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No data found',
            ]);
        }

        foreach ($queries as $query) {
            $query->telegram_group_count = $groupCount;
        }

        return response()->json([
            'success' => true,
            'data' => $queries,
        ]);
    }

    public function show($customId)
    {
        $query = Query::query()
            ->where('custom_id', $customId)
            ->where('status', 17)
            ->where('is_finished', true)
            ->select('custom_id', 'count')
            ->first();

        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found',
            ]);
        }

        $groupCount = Group::query()->count();
        if (!$groupCount) {
            return response()->json([
                'success' => false,
                'message' => 'No groups found',
            ]);
        }


        $query->telegram_group_count = $groupCount;




        return response()->json([
            'success' => true,
            'message' => 'Data fetched',
            'data' => $query,
        ]);
    }
}
