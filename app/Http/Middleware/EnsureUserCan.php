<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $team = $request->route('team');
        $channel = $request->route('channel');
        $user = $request->user();

        if (! $team || ! $channel || ! $user) {
            abort(404);
        }

        if ($channel->team_id !== $team->id) {
            abort(404);
        }

        if ($channel->is_private && ! $channel->users()->whereKey($user->id)->exists()) {
            abort(403);
        }

        app()->instance('currentChannel', $channel);

        return $next($request);
    }
}
