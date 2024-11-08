<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // Register a new user and send OTP via Fast2SMS
    public function signUp(Request $request)
    {
        // Log the incoming request payload for debugging
        Log::info('SignUp Request Payload:', $request->all());

        // Validate the request input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users',
            'phone' => 'required|digits:10|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Handle validation failure with 400 Bad Request status
        if ($validator->fails()) {
            Log::warning('Validation Failed:', $validator->errors()->toArray());
            return response()->json([
                'message' => 'Validation failed. Please check your input.',
                'errors' => $validator->errors()
            ], 400);  // 400 Bad Request
        }

        // Start a database transaction
        DB::beginTransaction();
        try {
            // Create a new user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'otp' => mt_rand(1000, 9999), // Generate OTP
            ]);

            // Send OTP to the user's phone
            $this->sendSms($user->phone, $user->otp);

            // Commit the transaction
            DB::commit();

            // Log successful user creation
            Log::info('User registered successfully:', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'User registered successfully. Please verify your phone with the OTP sent.',
                'user_id' => $user->id
            ], 201);  // 201 Created
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();

            // Log the exception details for debugging
            Log::error('User registration failed:', ['error' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);

            return response()->json([
                'message' => 'Registration failed due to a server error. Please try again later.',
                'error' => $e->getMessage()
            ], 500);  // 500 Internal Server Error
        }
    }
    // Login a user
    public function signIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|digits:10',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        if (Auth::attempt(['phone' => $request->phone, 'password' => $request->password])) {
            $user = Auth::user();
            if (is_null($user->phone_verified_at)) {
                return response()->json(['error' => 'Please verify your phone first.'], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                ]
            ], 200);
        }

        return response()->json(['error' => 'Invalid credentials'], 401);
    }

public function verifyPhoneOtp(Request $request)
{
    // Log the incoming request data
    Log::info('Incoming OTP Verification Request', ['payload' => $request->all()]);

    // Validate the request
    $validator = Validator::make($request->all(), [
        'phone' => 'required|digits:10',
        'otp' => 'required|digits:4',
    ]);

    if ($validator->fails()) {
        // Log the validation error
        Log::warning('OTP Verification Validation Failed', ['errors' => $validator->errors()]);
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Log phone and OTP for debugging (without logging sensitive data in production)
    Log::info('Validating OTP for phone number', ['phone' => $request->phone]);

    // Check if the user exists and if the OTP matches
    $user = User::where('phone', $request->phone)->first();
    if (!$user || $user->otp !== $request->otp) {
        // Log the failure
        Log::warning('Invalid phone number or OTP', ['phone' => $request->phone, 'provided_otp' => $request->otp]);
        return response()->json(['error' => 'Invalid phone number or OTP'], 400);
    }

    // Mark the phone as verified
    $user->phone_verified_at = now();
    $user->otp = null; // Clear the OTP after successful verification
    $user->save();

    // Log the success
    Log::info('Phone verification successful', ['user_id' => $user->id]);

    return response()->json(['message' => 'Phone verified successfully.'], 200);
}

    // Resend OTP for phone verification
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|digits:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json(['error' => 'Phone number not registered'], 400);
        }

        $user->otp = mt_rand(1000, 9999); // Generate new OTP
        $user->save();

        // Send OTP via Fast2SMS
        $this->sendSms($user->phone, $user->otp); // Send only numeric OTP

        return response()->json(['message' => 'OTP resent successfully'], 200);
    }

    // Reset password
    public function resetPassword(Request $request)
{
    // Validate the input
    $validator = Validator::make($request->all(), [
        'phone' => 'required|digits:10',
        'otp' => 'required|digits:4',
        'new_password' => 'required|string|min:6|confirmed',
    ]);

    // Check for validation errors
    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Retrieve the user based on the provided phone number
    $user = User::where('phone', $request->phone)->first();

    // Check if user exists and OTP matches
    if (!$user || $user->otp !== $request->otp) {
        return response()->json(['error' => 'Invalid phone number or OTP'], 400);
    }

    // If OTP is correct, proceed with resetting the password
    $user->password = Hash::make($request->new_password);
    $user->otp = null; // Clear the OTP after successful password reset
    $user->save();

    return response()->json(['message' => 'Password reset successfully.'], 200);
}


    // Function to send SMS via Fast2SMS
    private function sendSms($phone, $otp)
    {
        $apiKey = env('FAST2SMS_API_KEY');
        $response = Http::withHeaders([
            'authorization' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://www.fast2sms.com/dev/bulkV2', [
            'route' => 'otp', // Set the route to 'otp'
            'variables_values' => $otp, // Pass only the OTP (numeric value)
            'numbers' => $phone, // Pass the recipient's phone number
        ]);

        // Log the full response for debugging
        \Log::info('Fast2SMS Response:', ['response' => $response->json()]);

        // Check if the request was successful
        if ($response->successful()) {
            return $response->json(); // Return the full JSON response
        } else {
            \Log::error('Fast2SMS Error:', ['response' => $response->json()]);
            return $response->status(); // Return HTTP status code in case of failure
        }
    }

}
