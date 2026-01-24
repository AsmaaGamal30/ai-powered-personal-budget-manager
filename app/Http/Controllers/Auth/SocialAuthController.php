<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Socialite;
use Symfony\Component\HttpFoundation\Response;

class SocialAuthController extends Controller
{
    public function redirectToProvider(string $provider)
    {
        if (in_array($provider, ['google', 'facebook'])) {
            return Socialite::driver($provider)->stateless()->redirect();
        }

        return response()->json(['message' => 'Unsupported provider'], Response::HTTP_BAD_REQUEST);
    }

    public function handleProviderCallback(string $provider)
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            return response()->json(['message' => 'Unsupported provider'], Response::HTTP_BAD_REQUEST);
        }

        $socialUser = Socialite::driver($provider)->stateless()->user();

        $user = User::firstOrCreate(
            [
            'email' => $socialUser->email,
        ],
        [
            'name' => $socialUser->name ?? 'User Name',
            'email_verified_at' => now(),
        ]
        );

        $acessToken = $user->createToken('authToken')->plainTextToken;
        return response()->json([
            'access_token' => $acessToken,
            'user' => $user,
        ], Response::HTTP_OK);

    }
}
