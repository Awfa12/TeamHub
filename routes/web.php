<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ChannelController;
use Illuminate\Http\Request;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

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

                Route::post('/channel/{channel}/messages', [MessageController::class, 'store'])->name('channels.messages.store');
            });
        });

    // File routes
    Route::get('/files/{message}', [FileController::class, 'show'])->name('files.show');
    Route::get('/files/{message}/download', [FileController::class, 'download'])->name('files.download');

    // Health checks
    Route::get('/health/queue', function () {
        return Queue::getName() ? response()->json(['queue' => 'ok', 'connection' => config('queue.default')]) : response()->json(['queue' => 'ok']);
    })->name('health.queue');

    Route::get('/health/reverb', function () {
        // Simple Redis ping for Reverb/Redis availability
        try {
            Redis::connection()->ping();
            return response()->json(['reverb' => 'ok']);
        } catch (\Throwable $e) {
            return response()->json(['reverb' => 'failed', 'error' => $e->getMessage()], 500);
        }
    })->name('health.reverb');

    Route::get('/health/db', function () {
        try {
            DB::select('select 1');
            return response()->json(['db' => 'ok']);
        } catch (\Throwable $e) {
            return response()->json(['db' => 'failed', 'error' => $e->getMessage()], 500);
        }
    })->name('health.db');
});

require __DIR__.'/auth.php';
