<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $channel->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Team: {{ $team->name }}</p>
                    <p class="text-sm text-gray-500">{{ $channel->is_private ? 'Private channel' : 'Public channel' }}</p>
                    @if($channel->archived)
                        <div class="mt-2 text-xs text-red-600 bg-red-50 inline-flex items-center gap-2 px-2.5 py-1 rounded">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m2 8H7a2 2 0 01-2-2V6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v10a2 2 0 01-2 2z" />
                            </svg>
                            Archived (read-only)
                        </div>
                    @endif
                </div>
                <div class="space-x-3">
                    <a href="{{ route('channels.index', $team) }}" class="text-indigo-600 hover:underline">Back to channels</a>
                    <a href="{{ route('teams.show', $team) }}" class="text-gray-600 hover:underline">Team</a>
                </div>
            </div>

            @livewire('channel-chat', ['team' => $team, 'channel' => $channel])

            @can('update', $channel)
                <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
                    <h3 class="text-lg font-semibold">Manage Channel</h3>
                    <form action="{{ route('channels.update', [$team, $channel]) }}" method="POST" class="space-y-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input name="name" value="{{ old('name', $channel->name) }}" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            @error('name')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" rows="2">{{ old('description', $channel->description) }}</textarea>
                        </div>
                        <label class="inline-flex items-center space-x-2">
                            <input type="checkbox" name="is_private" value="1" class="rounded border-gray-300" {{ $channel->is_private ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">Private channel</span>
                        </label>
                        <div>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Save</button>
                        </div>
                    </form>

                    <div class="flex items-center space-x-4">
                        @if(! $channel->archived)
                            <form method="POST" action="{{ route('channels.archive', [$team, $channel]) }}">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md">Archive</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('channels.unarchive', [$team, $channel]) }}">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md">Unarchive</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>