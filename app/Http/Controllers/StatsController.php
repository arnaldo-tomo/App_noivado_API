<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth('api')->user();
        $userId = $user->id;

        $comments = Comment::where('user_id', $userId)->whereNull('parent_id')->count();
        $likes = \App\Models\Like::whereHas('comment', fn($q) => $q->where('user_id', $userId))->count();
        $present = Comment::where('user_id', $userId)->whereNull('parent_id')->where('presence', true)->count();
        $absent = Comment::where('user_id', $userId)->whereNull('parent_id')->where('presence', false)->count();

        return response()->json([
            'data' => [
                'comments' => $comments,
                'likes' => $likes,
                'present' => $present,
                'absent' => $absent,
            ],
        ]);
    }
}
