<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\File;
use App\Models\Folder;
use App\Models\UserLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DashboardController extends Controller implements HasMiddleware
{

    public static function middleware(): array
    {
        return [
            // new Middleware('permission_check:Dashboard,view', only: ['index', 'show']),
        ];
    }

    public function index()
    {
        $data = [];
        $company = Company::find(get_active_company());

        if (isset($company) && (auth()->user()->is_master_admin() || auth()->user()->is_super_admin())) {
            $today = Carbon::today('Asia/Kolkata');
            $companyId = get_active_company();

            // Existing login count
            $totalLogins = UserLog::where('action', 'LIKE', '%logged in%')
                ->where('company_id', $companyId)
                ->whereDate('created_at', $today)
                ->count();

            // New: Total viewed files today
            $totalViews = UserLog::where('action', 'LIKE', 'File % viewed')
                ->where('company_id', $companyId)
                ->whereDate('created_at', $today)
                ->count();

            // New: Total downloaded files today
            $totalDownloads = UserLog::where('action', 'LIKE', 'File % downloaded')
                ->where('company_id', $companyId)
                ->whereDate('created_at', $today)
                ->count();

            // New: Top 5 viewed files today (with counts)
            $topViewedFiles = UserLog::selectRaw('action, COUNT(*) as count')
                ->where('action', 'LIKE', 'File % viewed')
                ->where('company_id', $companyId)
                ->whereDate('created_at', $today)
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($log) {
                    preg_match('/File (.+?) viewed/', $log->action, $matches);
                    return [
                        'name' => $matches[1] ?? 'Unknown',
                        'count' => $log->count,
                    ];
                });

            // New: Top 5 downloaded files today (with counts)
            $topDownloadedFiles = UserLog::selectRaw('action, COUNT(*) as count')
                ->where('action', 'LIKE', 'File % downloaded')
                ->where('company_id', $companyId)
                ->whereDate('created_at', $today)
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($log) {
                    preg_match('/File (.+?) downloaded/', $log->action, $matches);
                    return [
                        'name' => $matches[1] ?? 'Unknown',
                        'count' => $log->count,
                    ];
                });

            // Existing space details
            $remainingSpace = getTotalUsedSpace();
            $usedSpaceMb = round((File::withTrashed()->where('company_id', $company->id)->sum('size_kb') / 1024)/1024, 2);

            $spaceDetails = [
                'total_space' => $company->storage_size_mb ?? 100,
                'used_space' => $usedSpaceMb ? $usedSpaceMb : 0,
                'available_space' => $remainingSpace ? round($remainingSpace / 1024, 2) : 0,
                'start_date' => $company->start_date ?? null,
                'end_date' => $company->end_date ?? null,
            ];

            $data['login_count'] = $totalLogins;
            $data['spaceDetails'] = (object) $spaceDetails;

            // New data for dashboard
            $data['totalViews'] = $totalViews;
            $data['totalDownloads'] = $totalDownloads;
            $data['topViewedFiles'] = $topViewedFiles;
            $data['topDownloadedFiles'] = $topDownloadedFiles;


        }

        return view('app.dashboard', $data);
    }
}
