<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $team->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Slug: {{ $team->slug }}</p>
                </div>
                <a href="{{ route('teams.index') }}" class="text-indigo-600 hover:underline">Back to teams</a>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-medium">Channels</h2>
                    @can('create', [\App\Models\Channel::class, $team])
                        <a href="{{ route('channels.index', $team) }}" class="text-indigo-600 hover:underline">Manage channels</a>
                    @else
                        <a href="{{ route('channels.index', $team) }}" class="text-gray-600 hover:underline">View channels</a>
                    @endcan
                </div>
                <p class="text-sm text-gray-600">Select a channel from the list to start chatting.</p>
            </div>
        </div>
    </div>
</x-app-layout>

