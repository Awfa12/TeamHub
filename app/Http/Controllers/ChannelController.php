<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Channel;
use App\Models\Team;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;

class ChannelController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request, Team $team)
    {
        $this->authorize('view', $team);
        $user = $request->user();
        $showArchived = $request->boolean('show_archived', false);

        // Public channels for the team + private channels the user belongs to
        $channels = Channel::where('team_id', $team->id)
            ->where(function ($q) use ($user) {
                $q->where('is_private', false)
                  ->orWhereHas('users', fn ($u) => $u->whereKey($user->id));
            })
            ->when(! $showArchived, fn ($q) => $q->where('archived', false))
            ->orderBy('archived')
            ->orderBy('name')
            ->get();

        return view('channels.index', [
            'channels' => $channels,
            'team' => $team,
            'showArchived' => $showArchived,
        ]);
    }

    public function store(Request $request, Team $team)
    {
        $this->authorize('view', $team);
        $this->authorize('create', [Channel::class, $team]);

        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_private' => 'nullable|boolean',
        ]);

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $suffix = 1;
        while (Channel::where('team_id', $team->id)->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        $channel = Channel::create([
            'team_id' => $team->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_private' => (bool)($validated['is_private'] ?? false),
            'creator_id' => $user->id,
            'archived' => false,
        ]);

        // If private, attach creator as participant
        if ($channel->is_private) {
            $channel->users()->attach($user->id, [
                'role' => 'owner',
                'joined_at' => now(),
            ]);
        }

        return redirect()->route('channels.show', [$team, $channel]);
    }

    public function show(Request $request, Team $team, Channel $channel)
    {
        $this->authorize('view', $team);
        $this->authorize('view', $channel);

        return view('channels.show', compact('channel', 'team'));
    }

    public function update(Request $request, Team $team, Channel $channel)
    {
        $this->authorize('update', $channel);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('channels')->where(fn ($q) => $q->where('team_id', $team->id))->ignore($channel->id),
            ],
            'description' => 'nullable|string',
            'is_private' => 'nullable|boolean',
        ]);

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $suffix = 1;
        while (
            Channel::where('team_id', $team->id)
                ->where('slug', $slug)
                ->where('id', '!=', $channel->id)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        $channel->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_private' => (bool)($validated['is_private'] ?? false),
        ]);

        return back()->with('status', 'Channel updated.');
    }

    public function archive(Request $request, Team $team, Channel $channel)
    {
        $this->authorize('update', $channel);
        $channel->update(['archived' => true]);
        return back()->with('status', 'Channel archived.');
    }

    public function unarchive(Request $request, Team $team, Channel $channel)
    {
        $this->authorize('update', $channel);
        $channel->update(['archived' => false]);
        return back()->with('status', 'Channel restored.');
    }
}
