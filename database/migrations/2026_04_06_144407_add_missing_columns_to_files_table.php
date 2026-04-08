<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->unsignedInteger('size_kb')->default(0)->after('updated_by');
            $table->bigInteger('item_index')->default(0)->after('size_kb');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn(['size_kb', 'item_index', 'deleted_at']);
        });
    }
};
