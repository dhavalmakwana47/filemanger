<?php

namespace App\Exports;

use App\Models\UserLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersLogExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithChunkReading,
    ShouldQueue
{
    use Exportable;

    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    // THIS IS THE KEY: Use FromQuery + query() to stream data
    public function query()
    {
        $user = auth()->user();
        $companyId = get_active_company(); // MUST return valid ID

        $query = UserLog::query()
            ->with(['user', 'company', 'company.admin_user']) // Eager load
            ->select('user_logs.*'); // Important!

        // Date filters
        if ($this->request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $this->request->from_date);
        }
        if ($this->request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $this->request->to_date);
        }

        // Admin filtering by user
        if (
            ($user->is_master_admin() || $user->is_super_admin()) &&
            $this->request->filled('user_id')
        ) {
            $query->where('user_id', $this->request->user_id);
        }

        // Company scope
        if ($user->is_master_admin() || $user->is_super_admin()) {
            $query->where('company_id', $companyId);
        } else {
            $query->where('company_id', $companyId)
                  ->where('user_id', $user->id);
        }

        // Extra filters
        if ($this->request->filled('user_id')) {
            $reqUser = User::find($this->request->user_id);
            if ($reqUser && $reqUser->type != 0) {
                $query->where('user_id', $this->request->user_id);
            }
        }

        if ($this->request->filled('claim_id')) {
            $query->where('claim_id', $this->request->claim_id);
        }

        return $query->orderBy('id', 'desc');
    }

    // Map each row
    public function map($log): array
    {
        return [
            $log->created_at?->format('d-M-Y h:i A') ?? '',
            $log->user?->name ?? '',
            wordwrap($log->action ?? '', 50, "\n", true),
            $log->ipaddress ?? '',
            $log->company?->name ?? '',
            $log->company?->admin_user?->name ?? '',
        ];
    }

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

    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 20,
            'C' => 45,
            'D' => 16,
            'E' => 30,
            'F' => 25,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFD9D9D9'],
                ],
            ],
            'A:F' => ['alignment' => ['wrapText' => true]],
        ];
    }
}