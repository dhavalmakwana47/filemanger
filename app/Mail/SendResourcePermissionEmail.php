<?php

namespace App\Mail;

use App\Models\User;
use App\Models\UserLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;

class SendResourcePermissionEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $folderNames;
    public $fileNames;
    public $companyName;
    public $companyId;
    public $userId;

    public function __construct(array $folderNames, array $fileNames, $companyName,$companyId,$userId)
    {
        $this->folderNames = $folderNames;
        $this->fileNames = $fileNames;
        $this->companyName = $companyName;
        $this->companyId = $companyId;
        $this->userId = $userId;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $user = User::find($this->userId);
        
        try {
            $email = $this->subject('New File(s)/Folder(s) Added in Data-room – ' . $this->companyName)
                        ->view('emails.resource_permission_granted')
                        ->with([
                            'folderNames' => $this->folderNames,
                            'fileNames' => $this->fileNames,
                            'companyName' => $this->companyName
                        ]);

            // Log successful email sending
            $successMessage = "Resource Permission Granted mail successfully sent to {$user->name}";
            $this->logAction($user, $successMessage);

            return $email;
        } catch (\Exception $e) {
            // Log the error
            $errorMessage = "Failed to send Resource Permission Granted mail to {$user->name}: " . $e->getMessage();
            $this->logAction($user, $errorMessage, 'error');
            
            // Re-throw the exception to allow Laravel's queue to handle the failure
            throw $e;
        }
    }

    /**
     * Log user action
     *
     * @param User $user
     * @param string $message
     * @param string $type
     * @return void
     */
    protected function logAction(User $user, string $message, string $type = 'info')
    {
        try {
            UserLog::create([
                'user_id' => $user->id,
                'ipaddress' => Request::ip(),
                'action' => $message,
                'company_id' => $this->companyId
            ]);
            
            // Also log to Laravel's log for better debugging
            if (in_array($type, ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'])) {
                \Illuminate\Support\Facades\Log::{$type}($message);
            } else {
                \Illuminate\Support\Facades\Log::info($message);
            }
        } catch (\Exception $e) {
            // If logging fails, log to the default Laravel log
            \Illuminate\Support\Facades\Log::error('Failed to log user action: ' . $e->getMessage());
        }
    }
}