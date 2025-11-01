<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $companyName;
    public $isNewUser;
    public $password;

    public function __construct($user, $companyName, $isNewUser, $password = null)
    {
        $this->user = $user;
        $this->companyName = $companyName;
        $this->isNewUser = $isNewUser;
        $this->password = $password;
    }

    public function build()
    {
        $subject = 'Login Information for Data-room Services (' . $this->companyName . ')';


        return $this->subject($subject)
            ->view('emails.user_registration')
            ->with([
                'user' => $this->user,
                'companyName' => $this->companyName,
                'isNewUser' => $this->isNewUser,
                'password' => $this->password
            ]);
    }
}
