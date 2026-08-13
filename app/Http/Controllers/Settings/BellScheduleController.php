<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\BellSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BellScheduleController extends Controller
{
    /**
     * Display list of bell schedules
     */
    public function index()
    {
        $schedules = BellSchedule::orderBy('time', 'asc')->get();
        return Inertia::render('Settings/BellSchedules', [
            'schedules' => $schedules
        ]);
    }

    /**
     * Store a new bell schedule
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'time' => 'required',
            'days' => 'required|array',
            'days.*' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'sound_type' => 'required|in:chime,custom,tts',
            'sound_file' => 'nullable|file|mimes:mp3,wav,ogg|max:10240', // Max 10MB
            'tts_text' => 'required_if:sound_type,tts|nullable|string',
            'tts_language' => 'required_if:sound_type,tts|nullable|string|in:id-ID,ja-JP,en-US',
            'volume' => 'required|integer|between:0,100',
            'is_active' => 'required|boolean',
        ]);

        $data = $request->only([
            'name', 'time', 'days', 'sound_type', 'tts_text', 'tts_language', 'volume', 'is_active'
        ]);

        if ($request->sound_type === 'custom' && $request->hasFile('sound_file')) {
            $file = $request->file('sound_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('bells', $filename, 'public');
            $data['sound_file'] = '/storage/' . $path;
        }

        BellSchedule::create($data);

        return back()->with('success', 'Jadwal bel berhasil ditambahkan.');
    }

    /**
     * Update an existing bell schedule
     */
    public function update(Request $request, BellSchedule $schedule)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'time' => 'required',
            'days' => 'required|array',
            'days.*' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'sound_type' => 'required|in:chime,custom,tts',
            'sound_file' => 'nullable|file|mimes:mp3,wav,ogg|max:10240', // Max 10MB
            'tts_text' => 'required_if:sound_type,tts|nullable|string',
            'tts_language' => 'required_if:sound_type,tts|nullable|string|in:id-ID,ja-JP,en-US',
            'volume' => 'required|integer|between:0,100',
            'is_active' => 'required|boolean',
        ]);

        $data = $request->only([
            'name', 'time', 'days', 'sound_type', 'tts_text', 'tts_language', 'volume', 'is_active'
        ]);

        if ($request->sound_type === 'custom') {
            if ($request->hasFile('sound_file')) {
                // Delete old file if exists
                if ($schedule->sound_file) {
                    $oldPath = str_replace('/storage/', '', $schedule->sound_file);
                    Storage::disk('public')->delete($oldPath);
                }
                $file = $request->file('sound_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('bells', $filename, 'public');
                $data['sound_file'] = '/storage/' . $path;
            }
        } else {
            // Delete old file if sound_type changed
            if ($schedule->sound_file) {
                $oldPath = str_replace('/storage/', '', $schedule->sound_file);
                Storage::disk('public')->delete($oldPath);
                $data['sound_file'] = null;
            }
        }

        $schedule->update($data);

        return back()->with('success', 'Jadwal bel berhasil diperbarui.');
    }

    /**
     * Delete a bell schedule
     */
    public function destroy(BellSchedule $schedule)
    {
        if ($schedule->sound_file) {
            $oldPath = str_replace('/storage/', '', $schedule->sound_file);
            Storage::disk('public')->delete($oldPath);
        }

        $schedule->delete();

        return back()->with('success', 'Jadwal bel berhasil dihapus.');
    }

    /**
     * Display the dedicated Bell Terminal page
     */
    public function terminal()
    {
        $schedules = BellSchedule::where('is_active', true)->orderBy('time', 'asc')->get();
        return Inertia::render('Settings/BellTerminal', [
            'schedules' => $schedules
        ]);
    }
}
