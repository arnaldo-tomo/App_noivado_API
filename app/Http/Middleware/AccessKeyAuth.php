<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class AccessKeyAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Try JWT first
        if ($request->bearerToken()) {
            $user = auth('api')->user();
            if ($user) {
                auth()->setUser($user);
                return $next($request);
            }
        }

        // Try access key
        $accessKey = $request->header('x-access-key');
        if ($accessKey) {
            $user = User::where('access_key', $accessKey)->first();
            if ($user) {
                auth()->setUser($user);
                return $next($request);
            }
        }

        return response()->json(['error' => ['Unauthorized']], 401);
    }
}
