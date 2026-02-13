<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function update(User $user, UpdateUserRequest $request)
    {
        $user = auth()->user();
        if ($user->id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $user->update($request->validated());

        return response()->json($user);
    }
}
