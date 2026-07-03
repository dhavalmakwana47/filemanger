<?php

namespace App\Jobs;

use App\Exports\UsersLogExport;
use App\Models\LogExport;
use App\Models\User;
use App\Models\UserLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportUserLogsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;
    public int $tries   = 1;

    public function __construct(
        private readonly int    $logExportId,
        private readonly int    $userId,
        private readonly int    $companyId,
        private readonly string $format,
        private readonly bool   $isAdmin,
        private readonly array  $filters,
    ) {}

    public function handle(): void
    {
        $export = LogExport::findOrFail($this->logExportId);
        $export->update(['status' => 'processing']);

        try {
            $filePath = $this->format === 'pdf'
                ? $this->generatePdf()
                : $this->generateExcel();

            $export->update(['status' => 'completed', 'file_path' => $filePath]);
        } catch (\Throwable $e) {
            Log::error('ExportUserLogsJob failed', ['id' => $this->logExportId, 'error' => $e->getMessage()]);
            $export->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }

    private function generateExcel(): string
    {
        $fileName = 'log_exports/user_logs_' . $this->logExportId . '.xlsx';

        Excel::store(
            new UsersLogExport($this->companyId, $this->userId, $this->isAdmin, $this->filters),
            $fileName,
            'local',
            \Maatwebsite\Excel\Excel::XLSX
        );

        return $fileName;
    }

    private function generatePdf(): string
    {
        $user     = User::find($this->userId);
        $fileName = 'log_exports/user_logs_' . $this->logExportId . '.pdf';
        $filePath = storage_path('app/' . $fileName);

        Storage::disk('local')->makeDirectory('log_exports');

        // Stream rows in chunks to avoid loading all into memory
        $rows        = [];
        $firstCompany = null;
        $firstUser    = null;
        $chunkSize   = 500;

        $this->buildQuery()
            ->with(['user:id,name', 'company:id,name'])
            ->chunk($chunkSize, function ($logs) use (&$rows, &$firstCompany, &$firstUser) {
                foreach ($logs as $log) {
                    if ($firstCompany === null) {
                        $firstCompany = $log->company?->name ?? '';
                        $firstUser    = $log->user?->name ?? '';
                    }
                    $rows[] = [
                        'date_time' => $log->created_at?->format('d-M-Y h:i A') ?? '',
                        'user_name' => $log->user?->name ?? '',
                        'action'    => $log->action ?? '',
                        'ip'        => $log->ipaddress ?? '',
                        'company'   => $log->company?->name ?? '',
                    ];
                }
            });

        $pdf = Pdf::loadView('exports.user-logs-pdf', [
            'logs'             => $rows,
            'exported_by'      => $user->name,
            'exported_at'      => now()->format('d M Y, h:i A'),
            'corporate_debtor' => $firstCompany ?? '',
            'personName'       => $firstUser ?? '',
        ])
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isRemoteEnabled'      => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled'         => false,
                'dpi'                  => 72,
                'margin_top'           => 10,
                'margin_right'         => 10,
                'margin_bottom'        => 15,
                'margin_left'          => 10,
            ]);

        file_put_contents($filePath, $pdf->output());
        unset($rows, $pdf);

        return $fileName;
    }

    private function buildQuery()
    {
        $query = UserLog::query()
            ->select('id', 'created_at', 'action', 'ipaddress', 'user_id', 'company_id')
            ->where('company_id', $this->companyId);

        if (! $this->isAdmin) {
            $query->where('user_id', $this->userId);
        } elseif (! empty($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }

        if (! empty($this->filters['from_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['from_date']);
        }
        if (! empty($this->filters['to_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['to_date']);
        }

        return $query->orderByDesc('id');
    }
}
