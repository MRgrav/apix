<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Notification;

class NotificationController extends Controller
{
    //

    public function create($userId = null, $title, $message, $type = 'individual'){
        
        $keys = Cache::keys('updates_*');

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        $notification = Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
        ]);

        return $notification;
    }

    public function fetchRecentUpdates () {
        
        $userId = auth()->id();

        $key = 'updates_' . $userId;

        if (Cache::has($key)) {
            $updates = json_decode(Cache::get($key), true); // Decode the JSON data
            return response()->json([
                'message' => 'fetched updates,',
                'courses' => $updates
            ], 200);
        }  

        $updates = Notification::where(function ($query) use ($userId) {
                        $query->where('user_id', $userId)
                            ->orWhere('type', 'broadcast');
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit(15)
                    ->get();

        Cache::put($key, $updates->toJson(), now()->addMinutes(1));

        if (!$updates) {
            return response()->json(['message' => 'No Updates'], 404);
        }

        return response()->json([
            'message' => 'fetched updates',
            'updates' => $updates
        ], 200);
    
    }
}
