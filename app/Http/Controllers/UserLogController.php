<?php

namespace App\Http\Controllers;

use App\Jobs\ExportUserLogsJob;
use App\Models\LogExport;
use App\Models\User;
use App\Models\UserLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;

class UserLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = [];
        $user = auth()->user();

        // Get users for the filter dropdown
        // $users = User::where('company_id', get_active_company())->get();

        if ($request->ajax()) {
            if ($user->is_master_admin() || $user->is_super_admin()) {
                $query = UserLog::with('user')->where('company_id', get_active_company());
            } else {
                $query = UserLog::with('user')->where('company_id', get_active_company())->where('user_id', $user->id);
            }

            // Apply date range filter
            if ($request->has('from_date') && $request->from_date != '') {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->has('to_date') && $request->to_date != '') {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Apply user filter for admins
            if (($user->is_master_admin() || $user->is_super_admin()) && $request->has('user_id') && $request->user_id != '') {
                $query->where('user_id', $request->user_id);
            }

            return Datatables::of($query)
                ->addIndexColumn()
                ->editColumn('created_at', function ($row) {
                    return Carbon::createFromFormat('Y-m-d H:i:s', $row->created_at)
                        ->timezone('Asia/Kolkata')
                        ->format('d-M-Y h:i A');
                })
                ->addColumn('user_name', function ($row) {
                    return $row->user ? $row->user->name : 'N/A';
                })
                ->filter(function ($query) use ($request) {
                    // Additional filtering can be added here if needed
                })

                ->rawColumns(['user'])
                ->escapeColumns([])
                ->make(true);
        }
        $users = User::whereHas('companies', function ($q) {
            $q->where('company_id', get_active_company());
        })->get();
        return view('app.userlog.list', compact('users'));
    }

    public function getusers(Request $request)
    {
        $userType = $request->user_type;
        if ($userType == 'admin') {
            $users = User::where('type', '0')->get();
        } elseif ($userType == 'scrutinizer') {
            $users = User::where('user_type', 2)->get();
        } elseif ($userType == 'ar') {
            $users = User::where('user_type', 1)->get();
        } else {
            $users = [];
        }
        $data['users'] = $users;

        return view('app.dd-html.userdropdown', $data);
    }



    public function userlog_download(Request $request)
    {
        $user    = auth()->user();
        $format  = $request->get('export_type', 'xlsx');
        $isAdmin = $user->is_master_admin() || $user->is_super_admin();

        $export = LogExport::create([
            'user_id' => $user->id,
            'format'  => $format,
            'status'  => 'pending',
        ]);

        ExportUserLogsJob::dispatch(
            $export->id,
            $user->id,
            get_active_company(),
            $format,
            $isAdmin,
            $request->only(['from_date', 'to_date', 'user_id', 'claim_id']),
        );

        addUserAction([
            'user_id' => $user->id,
            'action'  => "User '{$user->name}' queued log export in " . strtoupper($format),
        ]);

        return response()->json([
            'export_id'    => $export->id,
            'redirect_url' => route('userlog.exports.index'),
        ]);
    }

    public function exportsList()
    {
        if (request()->ajax()) {
            $exports = LogExport::where('user_id', auth()->id())->latest()->get();

            return DataTables::of($exports)
                ->addIndexColumn()
                ->addColumn('format', function ($row) {
                    return '<span class="badge badge-dark text-uppercase">' . $row->format . '</span>';
                })
                ->addColumn('status', function ($row) {
                    $map = [
                        'pending'    => ['badge-pending',    'fa-clock',        'Pending'],
                        'processing' => ['badge-processing', 'fa-spinner fa-spin', 'Processing'],
                        'completed'  => ['badge-completed',  'fa-check-circle', 'Completed'],
                        'failed'     => ['badge-failed',     'fa-times-circle', 'Failed'],
                    ];
                    [$cls, $icon, $label] = $map[$row->status] ?? ['badge-failed', 'fa-times-circle', 'Failed'];
                    return '<span class="status-badge ' . $cls . '"><i class="fas ' . $icon . '"></i> ' . $label . '</span>';
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at->timezone('Asia/Kolkata')->format('d-M-Y h:i A');
                })
                ->addColumn('actions', function ($row) {
                    $html = '';
                    if ($row->status === 'completed') {
                        $html .= '<a href="' . route('userlog.export.download', $row->id) . '" class="btn btn-sm btn-success download-btn" data-id="' . $row->id . '"><i class="fas fa-download"></i> Download</a> ';
                    } elseif (in_array($row->status, ['pending', 'processing'])) {
                        $html .= '<button class="btn btn-sm btn-info" disabled><i class="fas fa-spinner fa-spin"></i> Processing...</button> ';
                    }
                    if (!in_array($row->status, ['pending', 'processing'])) {
                        $html .= '<form action="' . route('userlog.exports.delete', $row->id) . '" method="POST" style="display:inline;" onsubmit="return confirm(\'Remove this export record?\')">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button></form>';
                    }
                    return $html;
                })
                ->rawColumns(['format', 'status', 'actions'])
                ->make(true);
        }

        return view('app.userlog.exports', ['hasPending' => false]);
    }

    public function exportDelete(int $id)
    {
        $export = LogExport::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        foreach ((array) $export->file_path as $path) {
            if ($path && Storage::exists($path)) {
                Storage::delete($path);
            }
        }

        $export->delete();

        return redirect()->route('userlog.exports.index')->with('success', 'Export record removed.');
    }

    public function exportDownload(int $id)
    {
        $export = LogExport::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->firstOrFail();

        $files = (array) $export->file_path;

        // Delete DB record immediately so re-clicking download is blocked
        $export->delete();

        if (count($files) === 1) {
            $path     = storage_path('app/' . $files[0]);
            $ext      = pathinfo($files[0], PATHINFO_EXTENSION);
            $mime     = $ext === 'pdf' ? 'application/pdf'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

            return response()->download($path, 'user_logs_export_' . $id . '.' . $ext, ['Content-Type' => $mime])
                ->deleteFileAfterSend(true);
        }

        // Multiple PDFs — zip them
        $zipPath = storage_path('app/log_exports/user_logs_export_' . $id . '.zip');

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($files as $filePath) {
            $abs = storage_path('app/' . $filePath);
            if (file_exists($abs)) {
                $zip->addFile($abs, basename($abs));
            }
        }
        $zip->close();

        // Delete individual PDFs now (zip already has them)
        foreach ($files as $filePath) {
            @unlink(storage_path('app/' . $filePath));
        }

        // deleteFileAfterSend deletes the zip after streaming
        return response()->download($zipPath, 'user_logs_export_' . $id . '.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }
}
