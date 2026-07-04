<?php

namespace App\Jobs;

use App\Exports\UsersLogExport;
use App\Models\LogExport;
use App\Models\UserLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;

class ExportUserLogsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;
    public int $tries   = 1;
    public int $memory  = 1024;

    private const CHUNK_SIZE     = 100;
    private const RECORDS_PER_PDF = 5000;

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
        Log::info("[ExportJob#{$this->logExportId}] Started", [
            'format'  => $this->format,
            'filters' => $this->filters,
        ]);

        $export = LogExport::findOrFail($this->logExportId);
        $export->update(['status' => 'processing']);

        try {
            $filePaths = $this->format === 'pdf'
                ? $this->generateChunkedPdfs()
                : [$this->generateExcel()];

            $export->update(['status' => 'completed', 'file_path' => $filePaths]);

            Log::info("[ExportJob#{$this->logExportId}] Completed", ['files' => $filePaths]);
        } catch (\Throwable $e) {
            Log::error("[ExportJob#{$this->logExportId}] Failed", [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
            $export->update(['status' => 'failed', 'error_message' => substr($e->getMessage(), 0, 500)]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("[ExportJob#{$this->logExportId}] Queue-level failure", [
            'error' => $e->getMessage(),
            'line'  => $e->getLine(),
        ]);

        LogExport::where('id', $this->logExportId)
            ->whereIn('status', ['pending', 'processing'])
            ->update(['status' => 'failed', 'error_message' => substr($e->getMessage(), 0, 500)]);
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

    private function generateChunkedPdfs(): array
    {
        ini_set('memory_limit', '1024M');
        Storage::disk('local')->makeDirectory('log_exports');
        Storage::disk('local')->makeDirectory('tmp');

        $totalCount = (clone $this->buildQuery())->count();

        Log::info("[ExportJob#{$this->logExportId}] Total records: {$totalCount}");

        if ($totalCount === 0) {
            return [$this->generateEmptyPdf()];
        }

        $totalParts = (int) ceil($totalCount / self::RECORDS_PER_PDF);
        $paths      = [];
        $lastId     = PHP_INT_MAX;

        for ($part = 1; $part <= $totalParts; $part++) {
            Log::info("[ExportJob#{$this->logExportId}] Generating part {$part}/{$totalParts}", [
                'memory_mb' => $this->memoryMb(),
            ]);

            [$path, $lastId] = $this->generatePdfPart($part, $totalParts, $lastId);
            $paths[] = $path;
            gc_collect_cycles();

            Log::info("[ExportJob#{$this->logExportId}] Part {$part} done", [
                'file'      => $path,
                'lastId'    => $lastId,
                'memory_mb' => $this->memoryMb(),
            ]);
        }

        return $paths;
    }

    private function generatePdfPart(int $part, int $totalParts, int $lastId): array
    {
        $label    = $totalParts > 1 ? "Part_{$part}_of_{$totalParts}" : 'All';
        $fileName = 'log_exports/user_logs_' . $this->logExportId . '_' . $label . '.pdf';
        $filePath = storage_path('app/' . $fileName);

        $mpdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4-L',
            'margin_top'       => 10,
            'margin_right'     => 8,
            'margin_bottom'    => 10,
            'margin_left'      => 8,
            'tempDir'          => storage_path('app/tmp'),
            'useSubstitutions' => false,
            'simpleTables'     => true,
        ]);

        $mpdf->SetTitle('User Logs - ' . str_replace('_', ' ', $label));
        $mpdf->WriteHTML('
            <style>
                body  { font-family: sans-serif; font-size: 9px; }
                h3    { font-size: 11px; margin-bottom: 6px; }
                table { width: 100%; border-collapse: collapse; }
                th    { background: #d9d9d9; font-weight: bold; padding: 4px 5px; border: 1px solid #999; }
                td    { padding: 3px 5px; border: 1px solid #ccc; word-break: break-word; }
                tr.even td { background: #f5f5f5; }
            </style>
            <h3>User Logs - ' . str_replace('_', ' ', $label) . '</h3>
            <table>
                <thead><tr>
                    <th>Date &amp; Time</th>
                    <th>User Name</th>
                    <th>Action</th>
                    <th>IP Address</th>
                    <th>Company</th>
                </tr></thead>
            <tbody>
        ');

        $even        = false;
        $written     = 0;
        $base        = $this->buildQuery();

        while ($written < self::RECORDS_PER_PDF) {
            $remaining = self::RECORDS_PER_PDF - $written;
            $limit     = min(self::CHUNK_SIZE, $remaining);

            $logs = (clone $base)
                ->where('user_logs.id', '<', $lastId)
                ->limit($limit)
                ->get();

            if ($logs->isEmpty()) {
                $lastId = 0; // signal outer loop to stop
                break;
            }

            $chunk = '';
            foreach ($logs as $log) {
                $class  = $even ? ' class="even"' : '';
                $even   = !$even;
                $chunk .= '<tr' . $class . '>'
                    . '<td>' . htmlspecialchars($log->created_at?->format('d-M-Y h:i A') ?? '') . '</td>'
                    . '<td>' . htmlspecialchars($log->user_name    ?? '') . '</td>'
                    . '<td>' . htmlspecialchars($log->action       ?? '') . '</td>'
                    . '<td>' . htmlspecialchars($log->ipaddress    ?? '') . '</td>'
                    . '<td>' . htmlspecialchars($log->company_name ?? '') . '</td>'
                    . '</tr>';
            }

            $mpdf->WriteHTML($chunk);
            $written += $logs->count();
            $lastId   = $logs->last()->id;
            unset($logs, $chunk);
            gc_collect_cycles();
        }

        $mpdf->WriteHTML('</tbody></table>');
        $mpdf->Output($filePath, 'F');
        unset($mpdf);

        return [$fileName, $lastId];
    }

    private function generateEmptyPdf(): string
    {
        $fileName = 'log_exports/user_logs_' . $this->logExportId . '_no_data.pdf';
        $filePath = storage_path('app/' . $fileName);

        $mpdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4-L',
            'margin_top'       => 10,
            'margin_right'     => 8,
            'margin_bottom'    => 10,
            'margin_left'      => 8,
            'tempDir'          => storage_path('app/tmp'),
            'useSubstitutions' => false,
            'simpleTables'     => true,
        ]);

        $mpdf->SetTitle('User Logs - No Data');
        $mpdf->WriteHTML('<style>body{font-family:sans-serif;font-size:11px;}</style><p>No log records found for the selected filters.</p>');
        $mpdf->Output($filePath, 'F');
        unset($mpdf);

        return $fileName;
    }

    private function buildQuery()
    {
        $query = UserLog::query()
            ->leftJoin('users',     'users.id',     '=', 'user_logs.user_id')
            ->leftJoin('companies', 'companies.id', '=', 'user_logs.company_id')
            ->select(
                'user_logs.id',
                'user_logs.created_at',
                'user_logs.action',
                'user_logs.ipaddress',
                'users.name as user_name',
                'companies.name as company_name',
            )
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

    private function memoryMb(): string
    {
        return round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB';
    }
}
