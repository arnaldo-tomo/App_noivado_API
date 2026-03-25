<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $token = auth('api')->attempt($credentials);

        if (!$token) {
            return response()->json(['error' => ['Invalid credentials']], 401);
        }

        return response()->json([
            'data' => ['token' => $token],
        ]);
    }
}
