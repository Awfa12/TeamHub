<?php

namespace App\Mail;

use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MessageReplyNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Message $message;
    public User $recipient;

    /**
     * Create a new message instance.
     */
    public function __construct(Message $message, User $recipient)
    {
        $this->message = $message;
        $this->recipient = $recipient;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New reply in ' . ($this->message->channel->name ?? 'channel'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.message-reply',
            with: [
                'message' => $this->message,
                'recipient' => $this->recipient,
                'channel' => $this->message->channel,
                'parent' => $this->message->parent,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
