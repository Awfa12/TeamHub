<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToTeam
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $team = $request->route('team');
        $user = $request->user();

        if (! $team || ! $user) {
            abort(404);
        }

        if (! $user->teams()->whereKey($team->id)->exists()) {
            abort(403);
        }

        app()->instance('currentTeam', $team);

        return $next($request);
    }
}
