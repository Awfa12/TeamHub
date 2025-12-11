<?php

namespace App\Jobs;

use App\Mail\MessageReplyNotification;
use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMessageNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $messageId;
    public int $recipientId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $messageId, int $recipientId)
    {
        $this->messageId = $messageId;
        $this->recipientId = $recipientId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $message = Message::with(['user', 'channel', 'parent.user'])->find($this->messageId);
        $recipient = User::find($this->recipientId);

        if (! $message || ! $recipient) {
            return;
        }

        Mail::to($recipient->email)
            ->send(new MessageReplyNotification($message, $recipient));
    }
}
