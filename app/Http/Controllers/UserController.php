<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function show(): JsonResponse
    {
        $user = auth('api')->user();

        return response()->json([
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'access_key' => $user->access_key,
                'tz' => $user->tz,
                'is_filter' => $user->is_filter,
                'is_confetti_animation' => $user->is_confetti_animation,
                'can_reply' => $user->can_reply,
                'can_edit' => $user->can_edit,
                'can_delete' => $user->can_delete,
                'tenor_key' => $user->tenor_key,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $updatable = [
            'filter' => 'is_filter',
            'confetti_animation' => 'is_confetti_animation',
            'can_reply' => 'can_reply',
            'can_edit' => 'can_edit',
            'can_delete' => 'can_delete',
            'tenor_key' => 'tenor_key',
            'name' => 'name',
            'tz' => 'tz',
        ];

        foreach ($updatable as $input => $column) {
            if ($request->has($input)) {
                $user->{$column} = $request->input($input);
            }
        }

        if ($request->has('old_password') && $request->has('new_password')) {
            if (!Hash::check($request->input('old_password'), $user->password)) {
                return response()->json(['error' => ['Current password is incorrect']], 422);
            }
            $user->password = $request->input('new_password');
        }

        $user->save();

        return response()->json(['data' => ['status' => true]]);
    }

    public function regenerateKey(): JsonResponse
    {
        $user = auth('api')->user();
        $user->access_key = Str::random(64);
        $user->save();

        return response()->json(['data' => ['status' => true]]);
    }
}
