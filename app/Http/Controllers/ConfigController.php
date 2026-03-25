<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    public function show(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'code' => 200,
            'data' => [
                'tz' => $user->tz,
                'is_filter' => $user->is_filter,
                'is_confetti_animation' => $user->is_confetti_animation,
                'is_comment_enabled' => true,
                'can_reply' => $user->can_reply,
                'can_edit' => $user->can_edit,
                'can_delete' => $user->can_delete,
                'tenor_key' => $user->tenor_key,
            ],
        ]);
    }
}
