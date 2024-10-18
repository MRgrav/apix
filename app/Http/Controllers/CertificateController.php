<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function uploadCertificate(Request $request, $userId)
    {
        // Validate the incoming request
        $request->validate([
            'certificate' => 'required|mimes:pdf,jpg,jpeg,png|max:2048', // Ensure this matches the expected file types
            'description' => 'nullable|string'
        ]);

        // Check if the file is uploaded
        if ($request->hasFile('certificate')) {
            // Store the file and get the file path
            $filePath = $request->file('certificate')->store('certificates');

            // Debugging: Log the file path
            \Log::info('Uploaded File Path: ' . $filePath);

            // Save the certificate details in the database
            $certificate = Certificate::create([
                'user_id' => $userId,
                'certificate_path' => $filePath, // Make sure this is set
                'description' => $request->description,
            ]);

            return response()->json(['message' => 'Certificate uploaded successfully!', 'certificate' => $certificate], 201);
        }

        return response()->json(['message' => 'No certificate file uploaded'], 400);
    }

    public function getCertificate($userId)
    {
        // Fetch the certificate from the database
        $certificate = Certificate::where('user_id', $userId)->first();

        if (!$certificate) {
            return response()->json(['message' => 'Certificate not found'], 404);
        }

        // Return the certificate data as JSON
        return response()->json($certificate);
    }
public function downloadCertificate($userId)
{
    // Get the certificate for the user
    $certificate = \App\Models\Certificate::where('user_id', $userId)->first();

    if (!$certificate) {
        return response()->json(['message' => 'Certificate not found'], 404);
    }

    // Return the file as a download response
    return response()->download(storage_path('app/' . $certificate->certificate_path));
}

}
