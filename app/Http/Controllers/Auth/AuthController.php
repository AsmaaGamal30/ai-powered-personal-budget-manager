<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Mail\Auth\SendOtpEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $otp = $request->otp;
        if (Cache::get('otp_' . $user->email) !== $otp) {
            return response()->json(['message' => 'Invalid OTP'], 401);
        }

        $accessToken = $user->createToken('authToken')->plainTextToken;
        return response()->json([
            'access_token' => $accessToken,
            'user' => $user,
        ]);
    }

    public function register(RegisterUserRequest $request)
    {
        $email = $request->email;
        $name = $request->name;
        $user = User::where('email', $email)->first();
        if ($user) {
            return response()->json(['message' => 'User already exists'], 409);
        }
        $user = User::create([
            'email' => $email,
            'name' => $name,
        ]);

        $otp = rand(10000, 99999);
        Cache::put('otp_' . $user->email, $otp, now()->addMinutes(10));

        Mail::to($email)->send(new SendOtpEmail($otp, $name));
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (Cache::get('otp_' . $user->email) !== $request->otp) {
            return response()->json(['message' => 'Invalid OTP'], 401);
        }

        Cache::forget('otp_' . $user->email);
        $user->email_verified_at = now();
        $user->save();

        $accessToken = $user->createToken('authToken')->plainTextToken;
        return response()->json([
            'access_token' => $accessToken,
            'user' => $user,
        ]);

    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $otp = rand(10000, 99999);

        Cache::put('otp_' . $user->email, $otp, now()->addMinutes(10));

        Mail::to($user->email)->send(new SendOtpEmail($otp, $user->name));

        return response()->json(['message' => 'OTP sent successfully']);
    }

    public function logout(User $user)
    {
        $user->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}