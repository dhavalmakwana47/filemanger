<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $company;
    public $user;
    public $password;



    public function __construct(Company $company, User $user, $password)
    {
        $this->company = $company;
        $this->user = $user;
        $this->password = $password; // Note: Avoid sending passwords in emails for security
    }

    public function build()
    {
        return $this->subject('Welcome to Data Safe Hub! Your ' . $this->company->name . ' Account is Ready to Secure Your Data')
            ->view('emails.company_register_email')
            ->with([
                'company' => $this->company,
                'user' => $this->user,
                'password' => $this->password,
            ]);
    }
}
