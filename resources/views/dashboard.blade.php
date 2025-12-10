<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Quick access</p>
                            <p class="text-lg font-semibold">Teams</p>
                        </div>
                        <a href="{{ route('teams.index') }}" class="text-indigo-600 hover:underline">Manage teams</a>
                    </div>

                    <div class="mt-4 space-y-3">
                        @forelse($teams as $team)
                            <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-md p-3">
                                <div>
                                    <div class="font-semibold">{{ $team->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $team->slug }}</div>
                                    <div class="text-xs text-gray-500">Channels: {{ $team->channels_count }}</div>
                                </div>
                                <div class="space-x-3">
                                    <a class="text-indigo-600 hover:underline" href="{{ route('teams.show', $team) }}">Team</a>
                                    <a class="text-indigo-600 hover:underline" href="{{ route('channels.index', $team) }}">Channels</a>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-600">No teams yet. <a href="{{ route('teams.index') }}" class="text-indigo-600 hover:underline">Create your first team</a>.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
