<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanExpiredMail extends Mailable
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
            subject: 'Your Company Plan Has Expired',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.plan-expired',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}