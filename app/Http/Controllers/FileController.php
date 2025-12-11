<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function show(Message $message)
    {
        // Check if user can access this message's channel
        $user = auth()->user();
        
        if (!$user) {
            abort(401);
        }

        // Check team membership
        if (!$user->teams()->where('team_id', $message->channel->team_id)->exists()) {
            abort(403);
        }

        if (!$message->file_path) {
            abort(404);
        }

        $disk = Storage::disk('minio');
        
        if (!$disk->exists($message->file_path)) {
            abort(404);
        }

        return response($disk->get($message->file_path), 200)
            ->header('Content-Type', $disk->mimeType($message->file_path))
            ->header('Content-Disposition', 'inline; filename="' . $message->file_name . '"');
    }

    public function download(Message $message)
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(401);
        }

        if (!$user->teams()->where('team_id', $message->channel->team_id)->exists()) {
            abort(403);
        }

        if (!$message->file_path) {
            abort(404);
        }

        $disk = Storage::disk('minio');
        
        if (!$disk->exists($message->file_path)) {
            abort(404);
        }

        return response($disk->get($message->file_path), 200)
            ->header('Content-Type', $disk->mimeType($message->file_path))
            ->header('Content-Disposition', 'attachment; filename="' . $message->file_name . '"');
    }
}

