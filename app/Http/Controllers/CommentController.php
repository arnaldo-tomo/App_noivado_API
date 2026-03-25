<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $perPage = (int) $request->query('per', 10);
        $next = $request->query('next');

        $query = Comment::where('user_id', $user->id)
            ->whereNull('parent_id')
            ->withCount('likes')
            ->with(['replies' => function ($q) {
                $q->withCount('likes')->orderBy('created_at', 'asc');
            }])
            ->orderBy('created_at', 'desc');

        if ($next) {
            $query->where('id', '<', $this->decodeNext($next));
        }

        $comments = $query->limit($perPage + 1)->get();

        $hasMore = $comments->count() > $perPage;
        $comments = $comments->take($perPage);

        $totalCount = Comment::where('user_id', $user->id)->whereNull('parent_id')->count();

        $lastComment = $comments->last();
        $nextCursor = $hasMore && $lastComment ? $this->encodeNext($lastComment->id) : null;

        return response()->json([
            'code' => 200,
            'data' => [
                'count' => $totalCount,
                'next' => $nextCursor,
                'lists' => $comments->map->toApiResponse()->values()->toArray(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'presence' => 'required|boolean',
            'comment' => 'nullable|string|max:1000',
            'id' => 'nullable|string',
            'gif_id' => 'nullable|string',
        ]);

        $parentId = null;
        if (!empty($validated['id'])) {
            $parent = Comment::where('uuid', $validated['id'])
                ->where('user_id', $user->id)
                ->first();
            $parentId = $parent?->id;
        }

        $gifUrl = null;
        if (!empty($validated['gif_id'])) {
            $gifUrl = 'https://tenor.googleapis.com/v2/posts?ids=' . $validated['gif_id'] . '&key=tenor';
        }

        $isAdmin = auth('api')->check();

        $comment = Comment::create([
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'name' => $validated['name'],
            'presence' => $validated['presence'],
            'comment' => $validated['comment'] ?? null,
            'is_admin' => $isAdmin,
            'gif_url' => $gifUrl,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $comment->loadCount('likes');
        $comment->load(['replies' => function ($q) {
            $q->withCount('likes');
        }]);

        return response()->json([
            'code' => 201,
            'data' => $comment->toApiResponse(),
        ], 201);
    }

    public function update(Request $request, string $own): JsonResponse
    {
        $user = auth()->user();
        $comment = Comment::where('own', $own)->where('user_id', $user->id)->first();

        if (!$comment) {
            return response()->json(['error' => ['Comment not found']], 404);
        }

        $validated = $request->validate([
            'presence' => 'nullable|boolean',
            'comment' => 'nullable|string|max:1000',
            'gif_id' => 'nullable|string',
        ]);

        if (isset($validated['presence'])) {
            $comment->presence = $validated['presence'];
        }

        if (array_key_exists('comment', $validated)) {
            $comment->comment = $validated['comment'];
        }

        if (!empty($validated['gif_id'])) {
            $comment->gif_url = 'https://tenor.googleapis.com/v2/posts?ids=' . $validated['gif_id'] . '&key=tenor';
        }

        $comment->save();

        return response()->json(['data' => ['status' => true]]);
    }

    public function destroy(string $own): JsonResponse
    {
        $user = auth()->user();
        $comment = Comment::where('own', $own)->where('user_id', $user->id)->first();

        if (!$comment) {
            return response()->json(['error' => ['Comment not found']], 404);
        }

        $comment->delete();

        return response()->json(['data' => ['status' => true]]);
    }

    public function like(Request $request, string $uuid): JsonResponse
    {
        $user = auth()->user();
        $comment = Comment::where('uuid', $uuid)->where('user_id', $user->id)->first();

        if (!$comment) {
            return response()->json(['error' => ['Comment not found']], 404);
        }

        $like = $comment->likes()->create([
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'code' => 201,
            'data' => ['uuid' => $like->uuid],
        ], 201);
    }

    public function unlike(string $uuid): JsonResponse
    {
        $user = auth()->user();

        $like = \App\Models\Like::where('uuid', $uuid)
            ->whereHas('comment', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->first();

        if (!$like) {
            return response()->json(['error' => ['Like not found']], 404);
        }

        $like->delete();

        return response()->json(['data' => ['status' => true]]);
    }

    public function download(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = auth('api')->user();

        $comments = Comment::where('user_id', $user->id)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="download.csv"',
        ];

        return response()->stream(function () use ($comments) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Presence', 'Comment', 'Created At', 'IP', 'User Agent']);

            foreach ($comments as $comment) {
                fputcsv($file, [
                    $comment->name,
                    $comment->presence ? 'Yes' : 'No',
                    $comment->comment,
                    $comment->created_at,
                    $comment->ip,
                    $comment->user_agent,
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }

    private function encodeNext(int $id): string
    {
        return base64_encode((string) $id);
    }

    private function decodeNext(string $next): int
    {
        return (int) base64_decode($next);
    }
}
