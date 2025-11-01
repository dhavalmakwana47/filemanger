<?php

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add initial records to the permissions table
        DB::table('permissions')->insert([
            [
                'name' => 'View',
                'slug' => 'view',
                'module_name' => 'Dashboard',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'View',
                'slug' => 'view',
                'module_name' => 'Users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Create',
                'slug' => 'create',
                'module_name' => 'Users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Update',
                'slug' => 'update',
                'module_name' => 'Users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Delete',
                'slug' => 'delete',
                'module_name' => 'Users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'View',
                'slug' => 'view',
                'module_name' => 'Company Role',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Create',
                'slug' => 'create',
                'module_name' => 'Company Role',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Update',
                'slug' => 'update',
                'module_name' => 'Company Role',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Delete',
                'slug' => 'delete',
                'module_name' => 'Company Role',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'View',
                'slug' => 'view',
                'module_name' => 'Company Permission',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Update',
                'slug' => 'update',
                'module_name' => 'Company Permission',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'View',
                'slug' => 'view',
                'module_name' => 'Folder',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Create',
                'slug' => 'create',
                'module_name' => 'Folder',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Update',
                'slug' => 'update',
                'module_name' => 'Folder',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Delete',
                'slug' => 'delete',
                'module_name' => 'Folder',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $user = User::create([
            'name' => 'Master Admin',
            'email' => 'masteradmin@gmail.com',
            'password' => Hash::make('masteradmin@123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $role = Role::create([
            'name' => 'Master Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inital_data');
    }
};
