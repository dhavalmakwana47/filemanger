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

        return response()->json(['export_id' => $export->id]);
    }

    public function exportStatus(int $id)
    {
        $export = LogExport::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($export->status !== 'completed') {
            return response()->json(['status' => $export->status]);
        }

        return response()->json([
            'status'       => 'completed',
            'download_url' => route('userlog.export.download', $export->id),
        ]);
    }

    public function exportDownload(int $id)
    {
        $export = LogExport::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->firstOrFail();

        $path = storage_path('app/' . $export->file_path);

        $mime = $export->format === 'pdf' ? 'application/pdf'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $fileName = 'user_logs_' . now()->format('Y-m-d_H-i-s') . '.' . $export->format;

        return response()->download($path, $fileName, ['Content-Type' => $mime])
            ->deleteFileAfterSend(true);
    }
}
