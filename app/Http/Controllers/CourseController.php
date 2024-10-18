<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Purchase;
use App\Models\Trial;
use App\Models\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    public function index()
    {
        // Retrieve all courses with related data
        return Course::with('category', 'sections', 'instructor')->get();
    }

    public function show($id)
    {
        $user = auth()->user();

        if (!$user) {
            \Log::error('User is not authenticated');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

    $course = Course::with('category', 'sections', 'instructor')->findOrFail($id);

    // Check if user has an active trial for this course
    $trial = $user->trials()->where('course_id', $id)->first();

    if ($trial && !$trial->isActive()) {
        return response()->json([
            'message' => 'Your trial has ended. Please purchase the course to continue access.'
        ], 403);
    }

    // Check if user has purchased the course
    $hasPurchased = $user->purchases()->where('course_id', $id)->exists();

    if (!$hasPurchased && !$trial) {
        return response()->json([
            'message' => 'You need to start a trial or purchase this course to access the content.'
        ], 403);
    }

    // If the trial is active or the course is purchased, show the content
    return response()->json($course, 200);
}


    public function store(Request $request)
{
    // Validate the request data
    $validatedData = $request->validate([
        'title' => 'required|string|max:255',
        'slug' => 'required|string|unique:courses,slug|max:255',
        'description' => 'nullable|string',
        'course_category_id' => 'nullable|exists:course_categories,id',
        'instructor_id' => 'nullable|exists:users,id',
        'thumbnail' => 'required|file|mimes:jpeg,png,jpg,gif|max:2048', // Thumbnail validation
    ]);

    // Handle the thumbnail upload if it exists
    if ($request->hasFile('thumbnail')) {
        $thumbnailFile = $request->file('thumbnail');
        $thumbnailPath = $thumbnailFile->store('images/thumbnails');
        $fileName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME) . '.' . $thumbnailFile->getClientOriginalExtension(); // Fallback for file name

        // Store the file information in the uploads table
        $upload = Uploads::create([
            'file_path' => $thumbnailPath,
            'file_name' => $fileName, // Set the file_name correctly
            'file_type' => 'thumbnail',
            'mime_type' => $thumbnailFile->getMimeType(),
            'uploaded_by' => auth()->check() ? auth()->id() : null, // Optional if using authentication
        ]);

        // Add the upload ID to the course's thumbnail field
        $validatedData['thumbnail'] = $upload->id;
    }

    // Create a new course
    $course = Course::create($validatedData);
    return response()->json($course, 201);
}


    public function update(Request $request, $id)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:courses,slug,'.$id,
            'description' => 'nullable|string',
            'course_category_id' => 'nullable|exists:course_categories,id',
            'instructor_id' => 'nullable|exists:users,id',
        ]);

        // Find and update the course
        $course = Course::findOrFail($id);
        $course->update($validatedData);

        return response()->json($course, 200);
    }

    public function destroy($id)
    {
        // Find and delete the course
        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json(null, 204);
    }

    public function updateCourseContent(Request $request, $id)
    {
        Log::info('Updating course content for course ID: ' . $id);

        // Validate the request data
        Log::info('Validating request data...');
        $validatedData = $request->validate([
            'live_class_link' => 'nullable|url|max:255',
            'pre_recorded_videos' => 'nullable|array',
            'pre_recorded_videos.*' => 'file|mimes:mp4,mov,avi|max:51200',
        ]);
        Log::info('Request validated successfully.');

        $course = Course::findOrFail($id);
        Log::info('Course found with ID: ' . $id);

        // Update the live class link if provided
        if (isset($validatedData['live_class_link'])) {
            $course->live_class_link = $validatedData['live_class_link'];
            Log::info('Live class link updated: ' . $validatedData['live_class_link']);
        }

        // Handle pre-recorded videos upload
        if ($request->hasFile('pre_recorded_videos')) {
            Log::info('Uploading videos...');
            $uploadedVideos = [];
            foreach ($request->file('pre_recorded_videos') as $video) {
                try {
                    $path = $video->store('videos');
                    Log::info('Uploaded video path: ' . $path);
                    $uploadedVideos[] = $path;
                } catch (\Exception $e) {
                    Log::error('Video upload failed: ' . $e->getMessage());
                    return response()->json(['message' => 'Video upload failed'], 500);
                }
            }

            // Merge with existing videos
            $existingVideos = $course->pre_recorded_videos ? json_decode($course->pre_recorded_videos) : [];
            $course->pre_recorded_videos = json_encode(array_merge($existingVideos, $uploadedVideos));
            Log::info('Pre-recorded videos updated: ' . json_encode($uploadedVideos));
        } else {
            Log::info('No videos uploaded for course ID: ' . $id);
        }

        // Save the course
        try {
            $course->save();
            Log::info('Course content updated successfully for course ID: ' . $id);
        } catch (\Exception $e) {
            Log::error('Course save failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update course content'], 500);
        }

        return response()->json(['message' => 'Course content updated successfully', 'course' => $course], 200);
    }


    public function startTrial(Request $request, $courseId)
{
    $course = Course::findOrFail($courseId);

    // Check if the user already has an active trial for this course
    $existingTrial = Trial::where('user_id', auth()->id())
                          ->where('course_id', $courseId)
                          ->where('trial_end', '>', now())
                          ->first();

    if ($existingTrial) {
        return response()->json(['message' => 'Trial is already active for this course'], 400);
    }

    // Start a trial for 7 days (or your preferred duration)
    $trial = Trial::create([
        'user_id' => auth()->id(),
        'course_id' => $courseId,
        'trial_start' => now(),
        'trial_end' => now()->addDays(7),
    ]);

    return response()->json(['message' => 'Trial started successfully', 'trial' => $trial], 200);
}
public function createOrder(Request $request, $courseId)
{
    $course = Course::findOrFail($courseId);

    $razorpay = new \Razorpay\Api\Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

    $order = $razorpay->order->create([
        'receipt' => 'rcpt_' . auth()->id(),
        'amount' => $course->price * 100, // Razorpay expects amount in paise
        'currency' => 'INR',
        'payment_capture' => 1 // Auto-capture
    ]);

    return response()->json(['order_id' => $order['id'], 'amount' => $course->price, 'currency' => 'INR'], 200);
}
public function confirmPayment(Request $request)
{
    $validatedData = $request->validate([
        'razorpay_payment_id' => 'required|string',
        'razorpay_order_id' => 'required|string',
        'razorpay_signature' => 'required|string',
    ]);

    $razorpay = new \Razorpay\Api\Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

    $attributes = [
        'razorpay_order_id' => $request->razorpay_order_id,
        'razorpay_payment_id' => $request->razorpay_payment_id,
        'razorpay_signature' => $request->razorpay_signature
    ];

    try {
        $razorpay->utility->verifyPaymentSignature($attributes);

        // Store purchase details in 'purchases' table
        Purchase::create([
            'user_id' => auth()->id(),
            'course_id' => $request->course_id,
            'payment_id' => $request->razorpay_payment_id,
        ]);

        return response()->json(['message' => 'Payment successful, course purchased'], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Payment verification failed'], 400);
    }
}


}
