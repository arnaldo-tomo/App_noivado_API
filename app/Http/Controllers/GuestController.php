<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $filter = $request->query('filter'); // 'present', 'absent', or null for all

        $query = Comment::where('user_id', $user->id)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc');

        if ($filter === 'present') {
            $query->where('presence', true);
        } elseif ($filter === 'absent') {
            $query->where('presence', false);
        }

        $guests = $query->get(['uuid', 'name', 'presence', 'comment', 'ip', 'user_agent', 'created_at']);

        return response()->json([
            'data' => $guests->map(fn($g) => [
                'uuid' => $g->uuid,
                'name' => $g->name,
                'presence' => $g->presence,
                'comment' => $g->comment,
                'created_at' => $g->created_at->toISOString(),
            ])->values()->toArray(),
        ]);
    }
}
