<?php

namespace App\Mail;

use App\Models\CareerOpening;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CareerOpeningPublished extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public CareerOpening $opening)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "تم نشر وظيفة جديدة: {$this->opening->title}");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.submissions.career-opening-published',
            with: ['publicUrl' => url(route('jobs.show', $this->opening, false))],
        );
    }
}
