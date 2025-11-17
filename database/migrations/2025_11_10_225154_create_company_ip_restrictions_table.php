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
        Schema::create('company_ip_restrictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('setting_id');
            $table->string('ip_address', 45); // IPv4 or IPv6
            $table->string('label')->nullable(); // Optional description
            $table->timestamps();

            $table->foreign('setting_id')
                ->references('id')
                ->on('settings')
                ->onDelete('cascade');

            $table->unique(['setting_id', 'ip_address']);
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_ip_restrictions');
    }
};
