<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_report_schedules')) {
            Schema::create('whatsapp_report_schedules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('module', 40)->default('inventory')->index(); // inventory, sales, purchasing, manufacturing, logistics, finance
                $table->string('report_type', 60)->default('executive_summary'); // executive_summary, low_stock, stock_movement
                $table->string('frequency', 20)->default('weekly')->index(); // daily, weekly, monthly
                $table->string('send_time', 10)->default('08:00'); // HH:mm
                $table->unsignedTinyInteger('day_of_week')->nullable(); // 1 (Mon) - 7 (Sun)
                $table->unsignedTinyInteger('day_of_month')->nullable(); // 1 - 28
                $table->json('formats')->nullable(); // ["pptx", "docx"]
                $table->string('target_type', 20)->default('phone'); // phone, group, both
                $table->json('recipients')->nullable(); // [{ type: 'phone'|'group', target: '628..'|'1203..@g.us', name: '...' }]
                $table->string('whatsapp_session', 30)->default('inventory'); // sales, purchasing, inventory, hr
                $table->boolean('include_ai_summary')->default(true);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('last_sent_at')->nullable();
                $table->string('last_status', 30)->nullable(); // success, failed
                $table->text('last_error')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_report_schedules');
    }
};
