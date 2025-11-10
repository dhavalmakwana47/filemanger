<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequest;
use App\Models\Setting;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class SettingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission_check:Settings,view', only: ['index', 'store'])

        ];
    }
    public function index()
    {
        $settings = Setting::where('company_id', get_active_company())->first();
        return view('app.settings.index', compact('settings'));
    }

    public function store(SettingRequest $request)
    {
        $data = [
            'company_id' => get_active_company(),
            'ip_restriction' => $request->ip_restriction ? 1 : 0,
            'enable_watermark' => $request->enable_watermark ? 1 : 0,
        ];

        if ($request->hasFile('watermark_image')) {
            $path = $request->file('watermark_image')->store('watermarks', 'public');
            $data['watermark_image'] = $path;
        }

        Setting::updateOrCreate(
            ['company_id' => get_active_company()],
            $data
        );

        return back()->with('success', 'Settings updated successfully!');
    }
}
