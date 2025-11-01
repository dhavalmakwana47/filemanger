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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->string('name');
            $table->foreignId('folder_id')->nullable()->constrained('folders')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->nullable();
            $table->foreignId('updated_by')->constrained('users')->nullable();
            $table->unsignedInteger('size_kb'); // Store size in KB
            $table->bigInteger('item_index')->default(0); // For ordering folders

            $table->timestamps();
            $table->softDeletes(); // Add this for soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
