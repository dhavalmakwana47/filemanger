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

class UserLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = [];
        $user = auth()->user();


        if ($request->ajax()) {
            if ($user->is_master_admin() || $user->is_super_admin()) {
                $data = UserLog::with('user')->where('company_id', get_active_company());
            } else {
                $data = UserLog::with('user')->where('company_id', get_active_company())->where('user_id',auth()->user()->id);
            }
            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('created_at', function ($row) {
                    return Carbon::createFromFormat('Y-m-d H:i:s', $row->created_at)
                        ->timezone('Asia/Kolkata')
                        ->format('d-M-Y h:i A');
                })
                ->addColumn('user_name', function ($row) {
                    return isset($row->user) ? $row->user->name : 'N/A';
                })

                ->filter(function ($query) use ($request) {
                    if ($request->has('user_id') && $request->user_id != "") {
                        $query->where('user_id', $request->user_id);
                    }
                })

                ->rawColumns(['user'])
                ->escapeColumns([])
                ->make(true);
        }
        $data['users'] = User::whereHas('companies', function ($q) {
            $q->where('company_id', get_active_company());
        })->get();
        return view('app.userlog.list', $data);
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



    public function userlog_downlaod(Request $request)
    {
        $user = auth()->user();
        addUserAction([
            'user_id' => $user->id,
            'action' => "User '{$user->name}' Exported Successfully."
        ]);
        return Excel::download(new UsersLogExport($request), 'userslogs.xlsx');
    }
}
