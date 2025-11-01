<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendResourcePermissionEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $folderNames;
    public $fileNames;
    public $companyName;

    public function __construct(array $folderNames, array $fileNames, $companyName)
    {
        $this->folderNames = $folderNames;
        $this->fileNames = $fileNames;
        $this->companyName = $companyName;
    }

    public function build()
    {
        return $this->subject('Resource Permission Granted')
                    ->view('emails.resource_permission_granted')
                    ->with([
                        'folderNames' => $this->folderNames,
                        'fileNames' => $this->fileNames,
                        'companyName' => $this->companyName
                    ]);
    }
}