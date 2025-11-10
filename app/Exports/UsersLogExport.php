<?php

namespace App\Exports;

use App\Models\Claim;
use App\Models\Resolution;
use App\Models\User;
use App\Models\UserLog;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersLogExport implements FromCollection, WithHeadings, WithStyles
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request; // Fix the property assignment
    }


    public function columnWidths(): array
    {
        return [
            'A' => 10,  // ID
            'B' => 20,  // Date
            'C' => 25,  // User Name
            'D' => 40,  // Action
            'E' => 20,  // IP
            'F' => 30,  // Company Name
            'G' => 25,  // Admin Name
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'alignment' => [
                    'wrapText' => true,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFD9D9D9'],
                ],
            ],
            // Data rows
            'A:G' => [
                'alignment' => [
                    'wrapText' => true,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                ],
            ],
            // Set row height
            'A1:G' . ($this->collection()->count() + 1) => [
                'rowHeight' => 20,
            ],
        ];
    }

    public function map($log): array
    {
        return [
            $log->id,
            $log->created_at->format('Y-m-d H:i:s'),
            $log->user->name ?? '',
            wordwrap($log->action, 50, "\n", true), // Wrap long text
            $log->ipaddress,
            $log->company->name ?? '',
            $log->company->admin_user->name ?? '',
        ];
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {

        // Build the base query for logs
        $logsQuery = UserLog::with(['user']);
        $user = auth()->user();

         // Apply date range filter
            if ($this->request->has('from_date') && $this->request->from_date != '') {
                $logsQuery->whereDate('created_at', '>=', $this->request->from_date);
            }
            
            if ($this->request->has('to_date') && $this->request->to_date != '') {
                $logsQuery->whereDate('created_at', '<=', $this->request->to_date);
            }
            
            // Apply user filter for admins
            if (($user->is_master_admin() || $user->is_super_admin()) && $this->request->has('user_id') && $this->request->user_id != '') {
                $logsQuery->where('user_id', $this->request->user_id);
            }

        // Filter logs based on user type
        // if ($user->type != 0) {
        //     $logsQuery->where('company_id', get_active_company())->where('user_id', $user->id);
        // }

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
                'Name Of Corporate' => $log->company->name ?? '',
                'Person Name' => $log->company->admin_user->name ?? '',
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
            'Name Of Corporate',
            'Person Name',
        ];
    }
}
