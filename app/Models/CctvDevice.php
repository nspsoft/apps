<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CctvDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand',
        'ip_address',
        'rtsp_port',
        'http_port',
        'username',
        'password',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rtsp_port' => 'integer',
        'http_port' => 'integer',
    ];

    public function channels()
    {
        return $this->hasMany(CctvChannel::class, 'device_id')->orderBy('sort_order', 'asc');
    }
}
