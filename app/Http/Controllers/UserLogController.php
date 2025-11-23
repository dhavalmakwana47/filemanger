<?php

namespace App\Http\Controllers;

use App\Exports\UsersLogExport;
use App\Models\Claim;
use App\Models\User;
use App\Models\UserLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $user = auth()->user();
        $format = $request->get('export_type', 'xlsx');

        $fileNameBase = 'user_logs_' . now()->format('Y-m-d_H-i-s');

        // Log the user action
        addUserAction([
            'user_id' => $user->id,
            'action' => "User '{$user->name}' exported logs in " . strtoupper($format) . " format"
        ]);

        // Handle PDF separately
        if ($format === 'pdf') {
            return $this->exportPdf($request, $fileNameBase);
        }

        // Default: Excel (XLSX)
        $fileName = $fileNameBase . '.xlsx';

        return Excel::download(
            new UsersLogExport($request),
            $fileName,
            \Maatwebsite\Excel\Excel::XLSX,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]
        );
    }

    private function exportPdf(Request $request, $fileNameBase)
    {
        $user = auth()->user();
        $companyId = get_active_company();

        // Build the same query as in UsersLogExport
        $query = UserLog::query()
            ->with(['user', 'company', 'company.admin_user']);

        // Apply all filters (same as in export class)
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->filled('claim_id')) {
            $query->where('claim_id', $request->claim_id);
        }

        // User filter for admins
        if (($user->is_master_admin() || $user->is_super_admin()) && $request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Company scope
        if ($user->is_master_admin() || $user->is_super_admin()) {
            $query->where('company_id', $companyId);
        } else {
            $query->where('company_id', $companyId)->where('user_id', $user->id);
        }

        // Extra user filter logic
        if ($request->filled('user_id')) {
            $reqUser = User::find($request->user_id);
            if ($reqUser && $reqUser->type != 0) {
                $query->where('user_id', $request->user_id);
            }
        }

        $logs = $query->orderBy('id', 'desc')->get();

        // Format data for blade (same as map() was doing)
        $data = $logs->map(function ($log) {
            return [
                'date_time' => $log->created_at?->format('d-M-Y h:i A') ?? '',
                'user_name' => $log->user?->name ?? '',
                'action'    => $log->action ?? '',
                'ip'        => $log->ipaddress ?? '',
                'company'   => $log->company?->name ?? '',
                'admin'     => $log->company?->admin_user?->name ?? '',
            ];
        });

        $corporate_debtor = $logs->first()->company?->name ?? '';
        $personName = $logs->first()->user?->name ?? '';

        return Pdf::loadView('exports.user-logs-pdf', [
            'logs'            => $data,
            'exported_by'     => auth()->user()->name,
            'exported_at'     => now()->format('d M Y, h:i A'),
            'corporate_debtor' => $corporate_debtor,
            'personName'      => $personName,
        ])
            ->setPaper('a4', 'landscape')           // Landscape = more width
            ->setOptions([
                'defaultFont'       => 'DejaVu Sans',   // Important for special characters
                'isRemoteEnabled'   => true,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled'      => false,
                'dpi'               => 150,
            ])
            ->download($fileNameBase . '.pdf');
    }
}
