<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ChannelController;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        $teams = $request->user()
            ->teams()
            ->withCount(['channels' => fn ($q) => $q->where('archived', false)])
            ->get();

        return view('dashboard', compact('teams'));
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');

    Route::prefix('team/{team:slug}')
        ->middleware(['team.member'])
        ->group(function () {
            Route::get('/', [TeamController::class, 'show'])->name('teams.show');

            Route::get('/channels', [ChannelController::class, 'index'])->name('channels.index');
            Route::post('/channels', [ChannelController::class, 'store'])->name('channels.store');

            Route::middleware('channel.access')->group(function () {
                Route::get('/channel/{channel}', [ChannelController::class, 'show'])->name('channels.show');
                Route::patch('/channel/{channel}', [ChannelController::class, 'update'])->name('channels.update');
                Route::post('/channel/{channel}/archive', [ChannelController::class, 'archive'])->name('channels.archive');
                Route::post('/channel/{channel}/unarchive', [ChannelController::class, 'unarchive'])->name('channels.unarchive');
            });
        });
});

require __DIR__.'/auth.php';
