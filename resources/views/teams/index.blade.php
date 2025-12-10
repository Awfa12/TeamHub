<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Your Teams') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow sm:rounded-lg p-4">
                <form action="{{ route('teams.store') }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Team name</label>
                        <input name="name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        @error('name')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Create Team</button>
                </form>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-4">
                <h2 class="text-lg font-medium mb-3">Existing Teams</h2>
                <ul class="divide-y divide-gray-200">
                    @forelse($teams as $team)
                        <li class="py-2 flex items-center justify-between">
                            <div>
                                <div class="font-semibold">{{ $team->name }}</div>
                                <div class="text-sm text-gray-500">{{ $team->slug }}</div>
                            </div>
                            <a href="{{ route('teams.show', $team) }}" class="text-indigo-600 hover:underline">Open</a>
                        </li>
                    @empty
                        <li class="py-2 text-gray-500">No teams yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>

