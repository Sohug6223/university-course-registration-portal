<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    public function sendOtp(Request $request)
    {
        // Validate email
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Check if user exists
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this email address.',
            ], 404);
        }

        // Generate 6-digit OTP
        $otp = (string) random_int(100000, 999999);

        // Remove previous OTP for this email
        PasswordResetOtp::where('email', $request->email)->delete();

        // Save new OTP
        PasswordResetOtp::create([
            'email' => $request->email,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5),
            'is_verified' => false,
        ]);

        // Send OTP to user's email
        Mail::to($request->email)->send(
            new PasswordResetOtpMail($otp)
        );

        return response()->json([
            'success' => true,
            'message' => 'OTP has been sent to your email.',
        ]);
    }

    public function verifyOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required|digits:6',
    ]);

    $resetOtp = PasswordResetOtp::where('email', $request->email)
        ->where('otp', $request->otp)
        ->where('is_verified', false)
        ->latest()
        ->first();

    if (!$resetOtp) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid OTP.',
        ], 400);
    }

    if ($resetOtp->expires_at->isPast()) {
        return response()->json([
            'success' => false,
            'message' => 'OTP has expired. Please request a new OTP.',
        ], 400);
    }

    $resetOtp->update([
        'is_verified' => true,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'OTP verified successfully.',
    ]);
}
public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);

    $resetOtp = PasswordResetOtp::where('email', $request->email)
        ->where('is_verified', true)
        ->latest()
        ->first();

    if (!$resetOtp) {
        return response()->json([
            'success' => false,
            'message' => 'Please verify OTP first.',
        ], 400);
    }

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found.',
        ], 404);
    }

    $user->password = $request->password;
    $user->save();

    $resetOtp->delete();

    return response()->json([
        'success' => true,
        'message' => 'Password reset successfully.',
    ]);
}
}