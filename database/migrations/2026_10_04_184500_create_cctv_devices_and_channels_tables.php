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
        Schema::create('cctv_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('NVR Hikvision Utama');
            $table->string('brand')->default('Hikvision');
            $table->string('ip_address')->default('192.168.1.100');
            $table->integer('rtsp_port')->default(554);
            $table->integer('http_port')->default(80);
            $table->string('username')->default('admin');
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cctv_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained('cctv_devices')->onDelete('cascade');
            $table->integer('channel_number')->default(1);
            $table->string('name');
            $table->string('zone')->default('production'); // security, production, warehouse, logistics, facility, office
            $table->string('location_description')->nullable();
            $table->string('stream_url')->nullable(); // Optional direct WebRTC / HLS / RTSP URL
            $table->string('resolution')->default('1080p');
            $table->integer('fps')->default(25);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cctv_channels');
        Schema::dropIfExists('cctv_devices');
    }
};
