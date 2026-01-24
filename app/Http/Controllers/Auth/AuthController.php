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
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }
        $otp = $request->otp;
        if (Cache::get('otp_' . $user->email) !== $otp) {
            return response()->json(['message' => 'Invalid OTP'], Response::HTTP_UNAUTHORIZED);
        }

        $accessToken = $user->createToken('authToken')->plainTextToken;
        Cache::forget('otp_' . $user->email);
        return response()->json([
            'access_token' => $accessToken,
            'user' => $user,
        ], Response::HTTP_OK);
    }

    public function register(RegisterUserRequest $request)
    {
        $email = $request->email;
        $name = $request->name;
        $user = User::where('email', $email)->first();
        if ($user) {
            return response()->json(['message' => 'User already exists'], Response::HTTP_CONFLICT);
        }
        $user = User::create([
            'email' => $email,
            'name' => $name,
        ]);

        $otp = rand(10000, 99999);
        Cache::put('otp_' . $user->email, $otp, now()->addMinutes(10));

        Mail::to($email)->send(new SendOtpEmail($otp, $name));
        return response()->json(['message' => 'User registered successfully. Please verify your email with the OTP sent.'], Response::HTTP_CREATED);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if (Cache::get('otp_' . $user->email) !== $request->otp) {
            return response()->json(['message' => 'Invalid OTP'], Response::HTTP_UNAUTHORIZED);
        }

        Cache::forget('otp_' . $user->email);
        $user->email_verified_at = now();
        $user->save();

        $accessToken = $user->createToken('authToken')->plainTextToken;
        return response()->json([
            'access_token' => $accessToken,
            'user' => $user,
        ], Response::HTTP_OK);

    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $otp = rand(10000, 99999);

        Cache::put('otp_' . $user->email, $otp, now()->addMinutes(10));

        Mail::to($user->email)->send(new SendOtpEmail($otp, $user->name));

        return response()->json(['message' => 'OTP sent successfully'], Response::HTTP_OK);
    }

    public function logout(User $user)
    {
        $user->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully'], Response::HTTP_OK);
    }
}