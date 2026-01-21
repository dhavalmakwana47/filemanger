<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$setting = App\Models\Setting::where('company_id', 1)->first();

if ($setting) {
    echo "Watermark enabled: " . ($setting->enable_watermark ? 'YES' : 'NO') . "\n";
    echo "Company ID: " . $setting->company_id . "\n";
} else {
    echo "No settings found for company_id 1\n";
    
    // Check all settings
    $allSettings = App\Models\Setting::all();
    echo "Total settings records: " . $allSettings->count() . "\n";
    foreach ($allSettings as $s) {
        echo "Company {$s->company_id}: watermark=" . ($s->enable_watermark ? 'YES' : 'NO') . "\n";
    }
}

// Check current user
$user = auth()->user();
if ($user) {
    echo "Current user: " . $user->email . "\n";
    echo "Is master admin: " . ($user->is_master_admin() ? 'YES' : 'NO') . "\n";
    echo "Is super admin: " . ($user->is_super_admin() ? 'YES' : 'NO') . "\n";
} else {
    echo "No authenticated user\n";
}