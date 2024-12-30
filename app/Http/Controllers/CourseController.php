<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Purchase;
use App\Models\Trial;
use App\Models\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Check if user has purchased the course
        $hasPurchased = $user->purchases()->where('course_id', $id)->exists();

        if (!$hasPurchased) {
            return response()->json([
                'message' => 'You need to purchase this course to access the content.'
            ], 403);
        }

        // If the course is purchased, show the content
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
        'classes_id' =>'required|string',
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


    /**
     * Create an order for the course
     *
     * @param  Request  $request
     * @param  int  $courseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrder(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);
        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }
        $user = auth()->user(); // Get the authenticated user

        // Convert is_nri to boolean explicitly and add debugging
        // $isNri = (bool) $user->is_nri;
        // \Log::info('User NRI status:', ['user_id' => $user->id, 'is_nri' => $isNri, 'raw_is_nri' => $user->is_nri]);

        // Determine currency and price
        // Safely cast is_nri to boolean
        $isNri = filter_var($user->is_nri, FILTER_VALIDATE_BOOLEAN);
        
        // Set currency and price
        // $currency = $isNri ? 'USD' : 'INR';
        $currency = 'INR';

        // Convert price to the smallest unit (paise for INR, cents for USD)
        $amount = $price * 100;

        // Create order in Razorpay
        $razorpay = new \Razorpay\Api\Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

        $order = $razorpay->order->create([
            'receipt' => 'rcpt_' . auth()->id(),
            'amount' => $amount, // Amount in smallest unit
            'currency' => $currency,
            'payment_capture' => 1 // Auto-capture
        ]);

        return response()->json([
            'order_id' => $order['id'],
            'amount' => $price,
            'currency' => $currency
        ], 200);
    }

    /**
     * Confirm the payment status and save the purchase details
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmPayment(Request $request)
    {
        Log::info('Incoming payment request', $request->all());

        // Validate the incoming request data
        $validatedData = $request->validate([
            'course_id' => 'required|integer',
            'plan_id'=> 'required|integer',
            'payment_id' => 'required|string',
            'payment_signature' => 'required|string',
        ]);

        try {
            // Fetch the course details
            $course = Course::findOrFail($validatedData['course_id']);

            // Initialize Razorpay API
            $razorpay = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

            // Verify the payment signature
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $validatedData['payment_id'],
                'razorpay_signature' => $validatedData['payment_signature'],
            ];

            $isValidSignature = $razorpay->utility->verifyPaymentSignature($attributes);
            if (!$isValidSignature) {
                return response()->json(['message' => 'Payment verification failed'], 400);
            }

            // If the payment is valid, store the purchase details
            Purchase::create([
                'user_id' => Auth::id(),
                'course_id' => $validatedData['course_id'],
                'payment_id' => $validatedData['payment_id'],
                'plan_id'=> $validatedData['required|integer'],
                'amount' => $course->price,
                'status' => 'success',
            ]);

            Log::info('Payment confirmed and purchase recorded', $validatedData);

            return response()->json(['message' => 'Payment successful'], 200);

        } catch (\Exception $e) {
            Log::error('Payment confirmation failed: ' . $e->getMessage());
            return response()->json(['message' => 'Payment confirmation failed'], 500);
        }
    }


    public function getPurchasedCourses()
    {
        if (auth()->check()) {
            $userId = auth()->id();

            // Fetch the purchased courses
            $purchasedCourses = Purchase::where('user_id', $userId)
                ->with('course')
                ->get();

            // Ensure course_id is correct and course exists
            $courses = $purchasedCourses->map(function ($purchase) {
                if ($purchase->course) {
                    return [
                        'course_id' => $purchase->course->id,  // Ensure this is an integer
                        'course_title' => $purchase->course->title,
                        'course_description' => $purchase->course->description,
                        'purchase_date' => $purchase->created_at->format('Y-m-d H:i:s'),
                    ];
                }
                return null; // Handle cases where the course doesn't exist
            })->filter(); // Remove null entries

            if ($courses->isEmpty()) {
                return response()->json(['message' => 'No courses purchased yet'], 404);
            }

            return response()->json([
                'message' => 'Purchased courses retrieved successfully',
                'courses' => $courses,
            ], 200);
        } else {
            return response()->json(['message' => 'User not authenticated'], 401);
        }
    }


}
