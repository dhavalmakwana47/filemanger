<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RestrictIpByCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if ($user->is_master_admin() || $user->is_super_admin()) {
            return $next($request);
        }

        // Skip if not authenticated
        if (!$user) {
            return $next($request);
        }

        // Get user's company
        $companyId = get_active_company() ?? null;

        if (!$companyId) {
            return $next($request); // No company, skip check
        }

        // Check if IP restriction is enabled for the company
        $setting = Setting::where('company_id', $companyId)->first();

        if (!$setting->ip_restriction) {
            return $next($request); // IP restriction disabled
        }

        $clientIp = $request->ip();
        // Check if client's IP is in allowed list
        $ipAllowed = $setting->ipRestrictions()->where('ip_address', $clientIp)->exists();

        if (!$ipAllowed) {
            abort(403, 'Access denied: Your IP address is not authorized for this company.');
        }

        return $next($request);
    }
}
