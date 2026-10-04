<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BellSchedule;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BellAgentController extends Controller
{
    /**
     * Heartbeat / Ping endpoint for client agents
     */
    public function ping(Request $request)
    {
        $now = Carbon::now();
        $activeSchedules = BellSchedule::where('is_active', true)->get();
        $checksum = md5($activeSchedules->toJson());

        return response()->json([
            'success' => true,
            'message' => 'JICOS Bell Agent API is online',
            'server_time' => $now->format('Y-m-d H:i:s'),
            'current_time_short' => $now->format('H:i:s'),
            'current_day' => $now->englishDayOfWeek,
            'schedule_count' => $activeSchedules->count(),
            'checksum' => $checksum,
        ]);
    }

    /**
     * Get all active bell schedules for client synchronization
     */
    public function schedules(Request $request)
    {
        $now = Carbon::now();
        $schedules = BellSchedule::where('is_active', true)
            ->orderBy('time', 'asc')
            ->get()
            ->map(function ($item) {
                $soundUrl = null;
                if ($item->sound_file) {
                    $soundUrl = url("/api/v1/bell-agent/sound/{$item->id}");
                }

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'time' => substr($item->time, 0, 5) . ':00', // Format HH:MM:00
                    'days' => $item->days ?? [],
                    'sound_type' => $item->sound_type,
                    'sound_url' => $soundUrl,
                    'sound_filename' => $item->sound_file ? basename($item->sound_file) : null,
                    'tts_text' => $item->tts_text,
                    'tts_language' => $item->tts_language ?? 'id-ID',
                    'volume' => $item->volume ?? 80,
                    'is_active' => (bool)$item->is_active,
                    'updated_at' => $item->updated_at ? $item->updated_at->toIso8601String() : null,
                ];
            });

        $checksum = md5($schedules->toJson());

        return response()->json([
            'success' => true,
            'server_time' => $now->format('Y-m-d H:i:s'),
            'current_day' => $now->englishDayOfWeek,
            'checksum' => $checksum,
            'data' => $schedules,
        ]);
    }

    /**
     * Download / stream audio file for specific schedule
     */
    public function sound($id)
    {
        $schedule = BellSchedule::findOrFail($id);
        if (!$schedule->sound_file) {
            return response()->json(['error' => 'No sound file configured'], 404);
        }

        $path = str_replace('/storage/', '', $schedule->sound_file);
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->response($path);
        }

        return response()->json(['error' => 'Audio file not found on disk'], 404);
    }
}
