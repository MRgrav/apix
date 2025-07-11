<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Purchase;
use App\Models\Trial;
use App\Models\Uploads;
use App\Models\CoursePlan;
use App\Models\GroupUser;
use App\Models\PaymentOrder;
use App\Models\ClassFrequency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

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

    public function getCourseDetailsById($id)
    {
        // Define cache key for the course details
        $cacheKey = 'course_' . $id;

        // Check if course data is cached
        if (Cache::has($cacheKey)) {
            // If data exists in cache, retrieve it
            $course = Cache::get($cacheKey);
            return response()->json($course, 200);
        }
        try {
            //code...
            $course = Course::with('category', 'sections', 'instructor')->findOrFail($id);

            // Cache the course data for future requests (e.g., cache for 1 hour)
            Cache::put($cacheKey, $course, now()->addMinutes(1));

            // If the course is purchased, show the content
            return response()->json($course, 200);
        } catch (\Throwable $e) {
            //throw $th;
            Log::error("Course fetch failed: ", $e->getMessage());
            return response()->json([
                'message' => 'Course not found.'
            ], 404);
        }
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
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                // 'slug' => 'required|string|unique:courses,slug,'.$id,
                'short_description' => 'required|string',
                'description' => 'nullable|string',
                'course_category_id' => 'nullable|exists:course_categories,id',
                // 'instructor_id' => 'nullable|exists:users,id',
                'topics' => 'nullable|string',
                'requirements' => 'nullable|string',
                'outcomes' => 'nullable|string',
                'meta_title' => 'string',
                'meta_description' => 'string',
            ]);

            // Find and update the course
            $course = Course::findOrFail($id);
            $course->update($validatedData);

            return response()->json($course, 200);
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            return response()->json('Internal server error', 500);
        }
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

    public function renewal($courseId) {
        try {
            //code...
            if (!auth()->id()) {
                return response()->json(['message'=>'not authorized'], 403);
            }

            $cacheKey = 'renew' . auth()->id() . $courseId;

            if (Cache::has($cacheKey)) {
                // If data exists in cache, retrieve it
                $content = json_decode(Cache::get($cacheKey), true);
                return response()->json($content, 200);
            }

            $groupUser = Purchase::with('course','plan')
                                    ->where('user_id', auth()->id())
                                    ->where('course_id', $courseId)
                                    ->first();
            // return plans
            // return class frequency id
            if (!$groupUser) {
                return response()->json(['message'=>'Nothing to renew'], 404);
            }

            Cache::put($cacheKey, json_encode($groupUser), now()->addMinutes(3));
            
            return response()->json($groupUser, 200);

        } catch (\Throwable $e) {
            //throw $e;
            Log::error("Renew : ". $e->getMessage());
            return response()->json(['message'=>'internal server error'], 500);
        }
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
        // Validate the request data
        $validatedData = $request->validate([
            'duration' => 'required',
            'plan_id' => 'required',
            'class_frequency_id' => 'required',
            'category' => 'required',
        ]);

        $classFrequency = ClassFrequency::findOrFail($validatedData['class_frequency_id']);

        $course = Course::findOrFail($courseId);
        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }
        $user = auth()->user(); // Get the authenticated user

        $plan = CoursePlan::findOrFail($validatedData['plan_id']);
        if (!$plan) {
            return response()->json(['error' => 'Plan not found'], 404);
        }

        if ( $plan->GST == 0 ) {
            $currency =  'USD';
        } else {
            $currency =  'INR';
        }

        if ($request->category == 1) {      // 1 means music
            $duration = $validatedData['duration'];
        } else {
            $duration = 12;
        }

        // Convert price to the smallest unit (paise for INR, cents for USD)
        // $amount = 100 * $validatedData['duration'] * $plan->current_rate + ($plan->current_rate * $plan->GST);    
        // Calculate the base price
        
        if ($request->category == 1) {
            $basePrice = $duration * $plan->current_rate * $classFrequency['classes_per_month'];
        } else {
            $basePrice = $plan->current_rate;
        }
        // Calculate GST
        $gstAmount = $basePrice * ($plan->GST / 100);
        // Total amount in smallest unit
        $amount = ($basePrice + $gstAmount) * 100; // Convert to paise


        // Create order in Razorpay
        $razorpay = new \Razorpay\Api\Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

        try {
            $order = $razorpay->order->create([
                'receipt' => 'rcpt_' . auth()->id(),
                'amount' => $amount,
                'currency' => $currency,
                'payment_capture' => 1
            ]);

            PaymentOrder::create([
                'order_id' => $order['id'],
                'amount' => $amount / 100, // Store amount in original currency unit
                'currency' => $currency,
                'user_id' => auth()->id(),
                'course_id' => $courseId,
                'class_frequency_id' => $classFrequency->id,
                'number_of_classes' => $classFrequency->classes_per_month * $duration,
                'class' => $request->class ?? 'na',
                'duration' => $request->duration ?? null,
            ]);

            // Return response with order details
            return response()->json([
                'order_id' => $order['id'],
                'amount' => $amount,
                'currency' => $currency
            ], 200);
        } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
            return response()->json(['error' => 'Razorpay error: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error: '. $amount . $e->getMessage()], 500);
        }
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
            'plan_id' => 'required|integer',
            'payment_id' => 'required|string',
            'payment_signature' => 'required',
            'duration' => 'required|integer|min:1',  // Ensure duration is positive
            'razorpay_order_id' => 'required',  // Ensure order_id is passed
            'class_frequency_id' => 'required',
        ]);
    
        try {
            $classFrequecny = ClassFrequency::findOrFail($validatedData['class_frequency_id']);

            // Fetch the course details
            $course = Course::findOrFail($validatedData['course_id']);
    
            // Initialize Razorpay API
            $razorpay = new \Razorpay\Api\Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
    
            // Verify the payment signature
            $attributes = [
                'razorpay_order_id' => $validatedData['razorpay_order_id'], // Use validated order_id
                'razorpay_payment_id' => $validatedData['payment_id'],
                'razorpay_signature' => $validatedData['payment_signature'],
            ];

            if ($request->category == 1) {      // 1 means music
                $duration = $validatedData['duration'];
            } else {
                $duration = 12;
            }

            Log::info('Verifying payment signature', $attributes);

             // Log the expected signature for debugging
            $expectedSignature = hash_hmac('sha256', $validatedData['razorpay_order_id'] . '|' . $validatedData['payment_id'], env('RAZORPAY_SECRET'));
            Log::info('Expected Signature: ' . $expectedSignature);
            
            $isValidSignature = $razorpay->utility->verifyPaymentSignature($attributes);
            // if (!$isValidSignature) {
            //     Log::error('Payment verification failed', $attributes);
            //     return response()->json(['message' => 'Payment verification failed'], 400);
            // }
    
            // Fetch the payment details using Razorpay API
            $payment = $razorpay->payment->fetch($validatedData['payment_id']);
            
            // Ensure the payment was captured successfully
            if ($payment->status !== 'captured') {
                return response()->json(['message' => 'Payment not captured'], 400);
            }
    
            // You can now access payment details like amount, status, and more
            $amount = $payment->amount; // Amount paid in the smallest unit (e.g., paise for INR, cents for USD)
            $paymentStatus = $payment->status; // Payment status (e.g., 'captured', 'failed', etc.)
            
            // Calculate the expiry date based on the plan's duration
            $expiryDate = Carbon::now()->addMonths($duration);  // Assuming duration is in months
    
            // If the payment is valid, store the purchase details
            Purchase::create([
                'user_id' => auth()->id(),
                'course_id' => $validatedData['course_id'],
                'payment_id' => $validatedData['payment_id'],
                'plan_id' => $validatedData['plan_id'],
                'amount' => $amount / 100,  // Convert from smallest unit to main unit
                'status' => $paymentStatus,  // Store the payment status
                'expiry_date' => $expiryDate,  // Store the calculated expiry date
                'class_frequency_id' => $classFrequecny->id,
                'number_of_classes' => $classFrequecny->classes_per_month,
                'class' => $request->class ?? 'na',
                // 'category' => $request->category,
            ]);

            $existGroup = GroupUser::where('user_id', auth()->id())
                                    ->where('course_id',$validatedData['course_id'])
                                    // ->whereNotNull('group_id')
                                    ->exists();

            if (!$existGroup) {
                // initializing groups for users
                GroupUser::create([
                    'user_id' => auth()->id(),
                    'course_id' => $validatedData['course_id'],
                    'expiry_date' => $expiryDate,
                    'plan_id' => $validatedData['plan_id'],
                    'class_counted' => 0,
                    'total_classes' => $request['number_of_classes'],
                    'class' => $request->class ?? 'na',
                    'category' => $request->category,
                ]);
            } else {
                // Find the GroupUser record by user_id and course_id
                $groupUser = GroupUser::where('user_id', auth()->id())
                                        ->where('course_id', $validatedData['course_id'])
                                        ->first();
                $groupUser->expiry_date = $expiryDate;
                $groupUser->plan_id = $request->plan_id;
                $groupUser->total_classes = $groupUser->total_classes + $request->number_of_classes;
                $groupUser->save();
            }

    
            Log::info('Payment confirmed and purchase recorded', $validatedData);

            Cache::forget('renew' . auth()->id() . $validatedData['course_id']);
    
            return response()->json(['message' => 'Payment successful'], 200);
    
        } catch (\Exception $e) {
            Log::error('Payment confirmation failed: ' . $e->getMessage());
            return response()->json(['message' => 'Payment confirmation failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function confirmRenewalPayment(Request $request)
    {
        Log::info('Incoming payment request', $request->all());
    
        // Validate the incoming request data
        $validatedData = $request->validate([
            'course_id' => 'required|integer',
            'plan_id' => 'required|integer',
            'payment_id' => 'required|string',
            'payment_signature' => 'required',
            'duration' => 'required|integer|min:1',  // Ensure duration is positive
            'razorpay_order_id' => 'required',  // Ensure order_id is passed
            'class_frequency_id' => 'required',
        ]);
    
        try {
            if ($request->category == 1) {
                $duration = $validatedData['duration'];
            } else {
                $duration = 12;
            }

            $classFrequecny = ClassFrequency::findOrFail($validatedData['class_frequency_id']);
            $course = Course::findOrFail($validatedData['course_id']);
            $razorpay = new \Razorpay\Api\Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
    
            // Verify the payment signature
            $attributes = [
                'razorpay_order_id' => $validatedData['razorpay_order_id'], // Use validated order_id
                'razorpay_payment_id' => $validatedData['payment_id'],
                'razorpay_signature' => $validatedData['payment_signature'],
            ];
            Log::info('Verifying payment signature', $attributes);

            // Fetch the payment details using Razorpay API
            $payment = $razorpay->payment->fetch($validatedData['payment_id']);
            // Ensure the payment was captured successfully
            if ($payment->status !== 'captured') {
                return response()->json(['message' => 'Payment not captured'], 400);
            }
            // You can now access payment details like amount, status, and more
            $amount = $payment->amount; // Amount paid in the smallest unit (e.g., paise for INR, cents for USD)
            $paymentStatus = $payment->status; // Payment status (e.g., 'captured', 'failed', etc.)

            Purchase::create([
                'user_id' => auth()->id(),
                'course_id' => $validatedData['course_id'],
                'payment_id' => $validatedData['payment_id'],
                'plan_id' => $validatedData['plan_id'],
                'amount' => $amount / 100,  // Convert from smallest unit to main unit
                'status' => $paymentStatus,  // Store the payment status
                'expiry_date' => Carbon::parse($existingPurchase->expiry_date)->addMonths($duration),  // Store the calculated expiry date
                'class_frequency_id' => $classFrequency->id,
                'number_of_classes' => $classFrequency->classes_per_month * $duration,
                'class' => $request->class ?? 'na',
            ]);

            $existGroup = GroupUser::where('user_id', auth()->id())
                                    ->where('course_id',$validatedData['course_id'])
                                    ->exists();

            if (!$existGroup) {
                // initializing groups for users
                GroupUser::create([
                    'user_id' => auth()->id(),
                    'course_id' => $validatedData['course_id'],
                    'expiry_date' => $expiryDate,
                    'plan_id' => $validatedData['plan_id'],
                    'class_counted' => 0,
                    'total_classes' => $classFrequency->classes_per_month * $duration,
                    'class' => $request->class ?? 'na',
                ]);
            } else {
                // Find the GroupUser record by user_id and course_id
                $groupUser = GroupUser::where('user_id', auth()->id())
                                        ->where('course_id', $validatedData['course_id'])
                                        ->first();
                $groupUser->expiry_date = Carbon::parse($groupUser->expiry_date)->addMonths($duration);
                $groupUser->plan_id = $request->plan_id;
                $groupUser->total_classes += $classFrequency->classes_per_month * $duration;
                $groupUser->save();
            }

    
            Log::info('Payment confirmed and purchase recorded', $validatedData);
            Cache::forget('renew' . auth()->id() . $validatedData['course_id']);
            return response()->json(['message' => 'Payment successful'], 200);
        } catch (\Exception $e) {
            Log::error('Payment confirmation failed: ' . $e->getMessage());
            return response()->json(['message' => 'Payment confirmation failed', 'error' => $e->getMessage()], 500);
        }
    }
    

    public function getPurchasedCourses()
    {
        if (auth()->check()) {
            $userId = auth()->id();

            // Define cache key with proper prefix
            $key = $userId . 'purchase';

            // Try to fetch the purchased courses from Redis cache
            if (Cache::has($key)) {
                $courses = json_decode(Cache::get($key), true); // Decode the JSON data
                return response()->json([
                    'message' => 'Purchased courses retrieved from cache successfully',
                    'courses' => $courses,
                ], 200);
            }          

            // Fetch the purchased courses
            $purchasedCourses = Purchase::where('user_id', $userId)
                ->with(['course','plan'])
                ->get();

            // Ensure course_id is correct and course exists
            $courses = $purchasedCourses->map(function ($purchase) {
                if ($purchase->course) {
                    return [
                        'course_id' => $purchase->course->id,  // Ensure this is an integer
                        'course_title' => $purchase->course->title,
                        'plan_name' => $purchase->plan->plan_name,
                        'plan_details' => $purchase->plan->plan_details,
                        // 'purchase_date' => $purchase->created_at->format('d-m-Y'),
                        // 'expiry_date' => $purchase->expiry_date->format('d-m-Y'),
                        'purchase_date' => $purchase->created_at instanceof \Carbon\Carbon ? $purchase->created_at->format('d-m-Y') : $purchase->created_at,
                        'expiry_date' => $purchase->expiry_date instanceof \Carbon\Carbon ? $purchase->expiry_date->format('d-m-Y') : $purchase->expiry_date,
                    ];
                }
                return null; // Handle cases where the course doesn't exist
            })->filter(); // Remove null entries

            if ($courses->isEmpty()) {
                return response()->json(['message' => 'No courses purchased yet'], 404);
            }

            // Cache the purchased courses in Redis for future requests (cache for 1 hour)
            Cache::put($key, $courses->toJson(), now()->addMinutes(1)); // Store the courses as JSON string

            return response()->json([
                'message' => 'Purchased courses retrieved successfully',
                'courses' => $courses,
            ], 200);
        } else {
            return response()->json(['message' => 'User not authenticated'], 401);
        }
    }


}
