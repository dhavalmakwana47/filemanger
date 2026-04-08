<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('permissions')->insert([
            ['module_name' => 'Documents', 'name' => 'View',   'slug' => 'view',   'created_at' => $now, 'updated_at' => $now],
            ['module_name' => 'Documents', 'name' => 'Create', 'slug' => 'create', 'created_at' => $now, 'updated_at' => $now],
            ['module_name' => 'Documents', 'name' => 'Update', 'slug' => 'update', 'created_at' => $now, 'updated_at' => $now],
            ['module_name' => 'Documents', 'name' => 'Delete', 'slug' => 'delete', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('permissions')->where('module_name', 'Documents')->delete();
    }
};
