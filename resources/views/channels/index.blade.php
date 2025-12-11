<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Channels for ') . $team->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Team</p>
                    <h1 class="text-2xl font-semibold">{{ $team->name }}</h1>
                </div>
                <a href="{{ route('teams.show', $team) }}" class="text-indigo-600 hover:underline">Back to team</a>
            </div>

            @can('create', [\App\Models\Channel::class, $team])
                <div class="bg-white shadow sm:rounded-lg p-4">
                    <form action="{{ route('channels.store', $team) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Channel name</label>
                            <input name="name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            @error('name')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description (optional)</label>
                            <textarea name="description" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" rows="2"></textarea>
                        </div>
                        <label class="inline-flex items-center space-x-2">
                            <input type="checkbox" name="is_private" value="1" class="rounded border-gray-300">
                            <span class="text-sm text-gray-700">Private channel</span>
                        </label>
                        <div>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Create Channel</button>
                        </div>
                    </form>
                </div>
            @endcan

            <div class="bg-white shadow sm:rounded-lg p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-medium">Channels</h2>
                    <form method="GET" class="flex items-center gap-2">
                        <label class="inline-flex items-center space-x-2 text-sm text-gray-600">
                            <input type="checkbox" name="show_archived" value="1" {{ $showArchived ? 'checked' : '' }} onchange="this.form.submit()" class="rounded border-gray-300">
                            <span>Show archived</span>
                        </label>
                    </form>
                </div>
                <ul class="divide-y divide-gray-200">
                    @forelse($channels as $channel)
                        <li class="py-2 flex items-center justify-between">
                            <div>
                                <div class="font-semibold flex items-center gap-2">
                                    <span>{{ $channel->name }}</span>
                                    @if($channel->archived)
                                        <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded">Archived</span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $channel->is_private ? 'Private' : 'Public' }}
                                </div>
                            </div>
                            <a href="{{ route('channels.show', [$team, $channel]) }}" class="text-indigo-600 hover:underline">Open</a>
                        </li>
                    @empty
                        <li class="py-2 text-gray-500">No channels yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>

