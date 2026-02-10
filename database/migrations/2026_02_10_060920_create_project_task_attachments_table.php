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
        if (!Schema::hasTable('project_task_attachments')) {
            Schema::create('project_task_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_task_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained(); // Uploader
                $table->string('file_path');
                $table->string('file_name');
                $table->string('file_type')->nullable(); // Mime type
                $table->unsignedBigInteger('file_size')->nullable(); // Bytes
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_task_attachments');
    }
};
