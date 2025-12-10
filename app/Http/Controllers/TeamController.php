<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TeamController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $this->authorize('viewAny', Team::class);

        $teams = $request->user()->teams()->get();
        return view('teams.index', compact('teams'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Team::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();

        // Simple unique slug per team name
        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $suffix = 1;
        while (Team::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        $team = Team::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'owner_id' => $user->id,
            'settings' => [],
            'active' => true,
        ]);

        // Attach creator as owner in pivot
        $user->teams()->attach($team->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        return redirect()->route('teams.show', $team);
    }

    public function show(Request $request, Team $team)
    {
        $this->authorize('view', $team);

        return view('teams.show', compact('team'));
    }
}
