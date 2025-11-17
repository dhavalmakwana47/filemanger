<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequest;
use App\Models\CompanyIpRestriction;
use App\Models\Setting;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission_check:Settings,view', only: ['index', 'store'])

        ];
    }

    public function index(Request $request)
    {
        $setting = Setting::with('ipRestrictions')
            ->firstOrCreate(
                ['company_id' => get_active_company()],
                ['ip_restriction' => false, 'enable_watermark' => false]
            );

        return view('app.settings.index', compact('setting'));
    }

    public function store(SettingRequest $request)
    {
        $companyId = get_active_company();

        $setting = Setting::firstOrCreate(['company_id' => $companyId]);

        $data = [
            'ip_restriction' => $request->boolean('ip_restriction'),
            'enable_watermark' => $request->boolean('enable_watermark'),
            'nda_content' => $request->input('nda_content'),
            'nda_content_enable' => $request->boolean('nda_content_enable'),
        ];

        if ($request->hasFile('watermark_image')) {
            if ($setting->watermark_image) {
                Storage::disk('public')->delete($setting->watermark_image);
            }
            $data['watermark_image'] = $request->file('watermark_image')->store('watermarks', 'public');
        }

        $setting->update($data);

        return back()->with('success', 'Settings saved!');
    }

    public function addIp(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'label' => 'nullable|string|max:50',
        ]);

        $setting = Setting::where('company_id', get_active_company())->firstOrFail();

        $exists = $setting->ipRestrictions()
            ->where('ip_address', $request->ip_address)
            ->exists();

        if ($exists) {
            return back()->withErrors(['ip_address' => 'This IP is already added.']);
        }

        $setting->ipRestrictions()->create([
            'ip_address' => $request->ip_address,
            'label' => $request->label,
        ]);

        return back()->with('ip_success', 'IP added!');
    }

    public function removeIp($id)
    {
        $setting = Setting::where('company_id', get_active_company())->firstOrFail();

        $ip = $setting->ipRestrictions()->findOrFail($id);
        $ip->delete();

        return back()->with('ip_deleted', 'IP removed.');
    }

    public function signNdaAgreement(Request $request)
    {
        session()->put('nda_agreement', true);
        addUserAction([
            'user_id' => auth()->id(),
            'action' => 'Signed NDA Agreement',
        ]);
        return redirect()->back()->with('success', 'NDA agreement signed! Now you can access the dashboard.');
    }
}
