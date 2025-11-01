<?php

namespace App\Exports;

use App\Models\Claim;
use App\Models\Resolution;
use App\Models\User;
use App\Models\UserLog;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersLogExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request; // Fix the property assignment
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {

        // Build the base query for logs
        $logsQuery = UserLog::with(['user']);

        // Filter logs based on user type
        // if ($user->type != 0) {
        //     $logsQuery->where('company_id', get_active_company())->where('user_id', $user->id);
        // }
        $user = auth()->user();

        if ($user->is_master_admin() || $user->is_super_admin()) {
            $logsQuery->where('company_id', get_active_company());
        } else {
            $logsQuery->where('company_id', get_active_company())->where('user_id', auth()->user()->id);
        }

        // Additional filters based on request
        if ($this->request->filled('user_id')) {
            $requestedUser = User::find($this->request->user_id);
            if ($requestedUser && $requestedUser->type != 0) {
                $logsQuery->where('user_id', $this->request->user_id);
            }
        }

        if ($this->request->filled('claim_id')) {
            $logsQuery->where('claim_id', $this->request->claim_id);
        }

        // Execute the query and map the results
        return $logsQuery->orderBy('id', 'desc')->get()->map(function ($log) {
            return [
                'Date & Time' => Carbon::parse($log->created_at)->format('d-M-Y h:i A'),
                'User Name' =>  $log->user->name ?? '', // Combined null checks
                'Action' => $log->action,
                'IP' => $log->ipaddress,
            ];
        });
    }

    // Define the headings
    public function headings(): array
    {
        return [
            'Date & Time',
            'User Name',
            'Action',
            'IP',
        ];
    }
}
