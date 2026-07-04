<?php

namespace App\Exports;

use App\Models\UserLog;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersLogExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    use Exportable;

    public function __construct(
        private readonly int   $companyId,
        private readonly int   $userId,
        private readonly bool  $isAdmin,
        private readonly array $filters,
    ) {}

    public function collection()
    {
        return $this->buildQuery()->cursor()->map(function ($log) {
            return [
                $log->created_at?->format('d-M-Y h:i A') ?? '',
                $log->user_name    ?? '',
                $log->action       ?? '',
                $log->ipaddress    ?? '',
                $log->company_name ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return ['Date & Time', 'User Name', 'Action', 'IP Address', 'Company'];
    }

    public function columnWidths(): array
    {
        return ['A' => 18, 'B' => 22, 'C' => 60, 'D' => 18, 'E' => 30];
    }

    public function styles(Worksheet $sheet): array
    {
        // Only style the header row — no wrapText on all columns (very expensive on large sheets)
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']],
            ],
        ];
    }

    private function buildQuery()
    {
        // JOIN instead of eager loading — avoids creating relation model objects per row
        $query = UserLog::query()
            ->select(
                'user_logs.created_at',
                'user_logs.action',
                'user_logs.ipaddress',
                'users.name as user_name',
                'companies.name as company_name',
            )
            ->leftJoin('users',     'users.id',     '=', 'user_logs.user_id')
            ->leftJoin('companies', 'companies.id', '=', 'user_logs.company_id')
            ->where('user_logs.company_id', $this->companyId);

        if (! $this->isAdmin) {
            $query->where('user_logs.user_id', $this->userId);
        } elseif (! empty($this->filters['user_id'])) {
            $query->where('user_logs.user_id', $this->filters['user_id']);
        }

        if (! empty($this->filters['from_date'])) {
            $query->whereDate('user_logs.created_at', '>=', $this->filters['from_date']);
        }
        if (! empty($this->filters['to_date'])) {
            $query->whereDate('user_logs.created_at', '<=', $this->filters['to_date']);
        }

        return $query->orderByDesc('user_logs.id');
    }
}
