<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\Group;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    /**
     * Display a listing of videos for a specific course group.
     */
    public function index($group_id)
    {
        $videos = Video::where('group_id', $group_id)->get();
        return response()->json($videos);
    }

    /**
     * Store a new video for a course group.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'group_id' => 'required|exists:groups,id', // Ensure the group exists
            'title' => 'required|string|max:255',
            'video' => 'required|file|mimes:mp4,mov,avi',
            'play_limit' => 'nullable|integer|min:1',
        ]);

        $video = new Video();
        $video->group_id = $validatedData['group_id'];
        $video->title = $validatedData['title'];

        if ($request->hasFile('video')) {
            $video->video_path = $request->file('video')->store('videos', 'public');
        }

        $video->play_limit = $validatedData['play_limit'] ?? 3; // Default to 3 plays if not provided
        $video->save();

        return response()->json(['message' => 'Video uploaded successfully'], 201);
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
