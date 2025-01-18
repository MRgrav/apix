<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VideoController extends Controller
{
    /**
     * Display a listing of videos for a specific course group.
     */
    public function index($group_id)
    {
        $key = 'videos' . $group_id;

        if (Cache::has($key)) {
            $videos = json_decode(Cache::get($key), true); // Decode the JSON data
            return response()->json($videos);
        }  

        $videos = Video::where('group_id', $group_id)->get();

        Cache::put($key, $videos->toJson(), now()->addMinutes(48));

        return response()->json($videos);
    }

    /**
     * Store a new video for a course group.
     */
    /**
 * Store a new video for a course group.
 */
public function store(Request $request)
{
    try {
        $validatedData = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'group_id' => 'required|exists:groups,id', // Ensure the group exists
            'title' => 'required|string|max:255',
            'video_link' => 'required|url', // Ensure the video link is a valid URL
            'play_limit' => 'nullable|integer|min:1',
        ]);

        $video = new Video();
        $video->group_id = $validatedData['group_id'];
        $video->course_id = $validatedData['course_id'];
        $video->title = $validatedData['title'];
        $video->video_path = $validatedData['video_link']; // Store the video link
        $video->play_limit = $validatedData['play_limit'] ?? 3; // Default to 3 plays if not provided
        $video->save();

        return response()->json(['message' => 'Video link saved successfully'], 201);
    } catch (\Exception $e) {
        \Log::error('Error saving video link: ' . $e->getMessage());
        return response()->json([
            'error' => 'An error occurred while saving the video link.',
            'details' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Display a specific video.
     */
    public function show($id)
    {
        $video = Video::findOrFail($id);
        return response()->json($video);
    }

    /**
     * Update a specific video.
     */
    public function update(Request $request, $id)
    {
        $video = Video::findOrFail($id);

        $validatedData = $request->validate([
            'title' => 'nullable|string|max:255',
            'video' => 'nullable|file|mimes:mp4,mov,avi',
            'play_limit' => 'nullable|integer|min:1',
        ]);

        if ($request->hasFile('video')) {
            $video->video_path = $request->file('video')->store('videos', 'public');
        }

        $video->title = $validatedData['title'] ?? $video->title;
        $video->play_limit = $validatedData['play_limit'] ?? $video->play_limit;
        $video->save();

        return response()->json(['message' => 'Video updated successfully']);
    }

    /**
     * Remove a specific video.
     */
    public function destroy($id)
    {
        $video = Video::findOrFail($id);
        $video->delete();

        return response()->json(['message' => 'Video deleted successfully']);
    }

    /**
     * Play the video and increment the play count if allowed.
     */
    public function play($id)
    {
        $video = Video::findOrFail($id);

        if ($video->incrementPlayCount()) {
            return response()->json([
                'message' => 'Playing video...',
                'times_played' => $video->times_played,
                'play_limit' => $video->play_limit
            ]);
        }

        return response()->json([
            'message' => 'Play limit reached for this video',
            'times_played' => $video->times_played,
            'play_limit' => $video->play_limit
        ], 403);
    }
}
