<?php

namespace App\Http\Middleware;

use App\Models\admins;
use App\Models\instructors;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserType
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $isAuthorized = match ($type) {
            'admin' => $user instanceof admins,
            'instructor' => $user instanceof instructors,
            'student' => $user instanceof User,
            default => false,
        };

        if (! $isAuthorized) {
            abort(403);
        }

        return $next($request);
    }
}
