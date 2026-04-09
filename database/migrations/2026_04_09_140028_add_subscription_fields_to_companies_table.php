<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('admin_id');
            $table->date('end_date')->nullable()->after('start_date');
            $table->unsignedInteger('storage_size_mb')->default(100)->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'storage_size_mb']);
        });
    }
};
