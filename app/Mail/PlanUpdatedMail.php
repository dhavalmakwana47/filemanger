<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Date & Time Extension for Data-room Services (' . $this->company->name . ')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.plan-updated',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}