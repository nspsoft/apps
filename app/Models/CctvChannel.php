<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CctvChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'channel_number',
        'name',
        'zone',
        'location_description',
        'stream_url',
        'resolution',
        'fps',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'channel_number' => 'integer',
        'fps' => 'integer',
        'sort_order' => 'integer',
    ];

    public function device()
    {
        return $this->belongsTo(CctvDevice::class, 'device_id');
    }

    /**
     * Get default RTSP URL for this channel from NVR device
     */
    public function getRtspUrlAttribute(): string
    {
        if ($this->stream_url) {
            return $this->stream_url;
        }

        $device = $this->device;
        if (!$device) {
            return '';
        }

        $user = $device->username ?: 'admin';
        $pass = $device->password ? ":{$device->password}@" : '@';
        $ip = $device->ip_address ?: '192.168.1.100';
        $port = $device->rtsp_port ?: 554;
        $chNum = str_pad($this->channel_number, 2, '0', STR_PAD_LEFT);

        // Hikvision RTSP format: /Streaming/Channels/{channel}01 (main) or {channel}02 (sub)
        return "rtsp://{$user}{$pass}{$ip}:{$port}/Streaming/Channels/{$chNum}01";
    }
}
