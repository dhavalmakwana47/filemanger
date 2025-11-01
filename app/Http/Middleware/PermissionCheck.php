<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $module, $permission): Response
    {
        $user = auth()->user();
        if (!$user->is_master_admin() && !$user->is_super_admin()) {
            $company = get_active_company();
            if (!$company) {
                return redirect()->route('accessdenied');
            }
            $companyUser = $user->companyUser;
            if (!$companyUser || !$companyUser->is_active) {        
                return redirect()->route('accessdenied');
            }   
        }
        // List of routes to exclude from the check

        $excludedRoutes = [
            'company.index',
            'company.create',
            'company.update',
            'company.store',
            'company.show',
            'company.destroy',
            'company.edit'
        ];

        // Check if the current route is in the excluded list
        if (!in_array($request->route()->getName(), $excludedRoutes) && !get_active_company() && !$user->is_master_admin()) {
            return redirect()->route('accessdenied');
        }

        if ($user->hasPermission($module, $permission)) {

            return $next($request);
        }
        return redirect()->route('accessdenied');
    }
}
