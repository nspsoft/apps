<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Master Titik Pos Patroli
        Schema::create('security_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. CP-01
            $table->string('name'); // e.g. Pos Gerbang Utama
            $table->text('description')->nullable();
            $table->string('area_zone')->nullable(); // e.g. Ring 1 - Produksi
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('geofence_radius')->default(50); // Meter toleransi
            $table->string('qr_token')->unique(); // Hash token untuk QR code
            $table->json('sop_items')->nullable(); // Checklist standar SOP pos
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Master Template Jadwal Shift Patroli
        Schema::create('security_patrol_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->string('name'); // e.g. Patroli Rutin Shift Pagi Putaran 1
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('checkpoints_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        // 3. Pivot Template Jadwal & Checkpoints
        Schema::create('security_patrol_schedule_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('security_patrol_schedules')->cascadeOnDelete();
            $table->foreignId('checkpoint_id')->constrained('security_checkpoints')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
        });

        // 4. Sesi Patroli Transaksi (Per Shift / Run)
        Schema::create('security_patrol_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_code')->unique(); // e.g. PTR-20261031-0001
            $table->foreignId('schedule_id')->nullable()->constrained('security_patrol_schedules')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('shift_name')->default('Shift 1');
            $table->date('patrol_date')->index();
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('total_checkpoints')->default(0);
            $table->integer('completed_checkpoints')->default(0);
            $table->integer('missed_checkpoints')->default(0);
            $table->integer('incidents_count')->default(0);
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'incomplete'])->default('in_progress');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 5. Rincian Scan Per Titik (Logs)
        Schema::create('security_patrol_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_session_id')->constrained('security_patrol_sessions')->cascadeOnDelete();
            $table->foreignId('checkpoint_id')->constrained('security_checkpoints')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('scanned_at')->index();
            $table->decimal('lat_actual', 10, 8)->nullable();
            $table->decimal('lng_actual', 11, 8)->nullable();
            $table->decimal('distance_meters', 8, 2)->nullable();
            $table->boolean('is_deviation')->default(false);
            $table->enum('condition_status', ['normal', 'incident'])->default('normal');
            $table->json('checklist_answers')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('ga_ticket_id')->nullable()->constrained('ga_tickets')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_patrol_logs');
        Schema::dropIfExists('security_patrol_sessions');
        Schema::dropIfExists('security_patrol_schedule_checkpoints');
        Schema::dropIfExists('security_patrol_schedules');
        Schema::dropIfExists('security_checkpoints');
    }
};
