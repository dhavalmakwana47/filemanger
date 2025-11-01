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
        Schema::create('company_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_role_id');
            $table->unsignedBigInteger('permission_id');
            
            $table->timestamps();

            $table->foreign('company_role_id')->references('id')->on('company_roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_role_permissions');
    }
};
