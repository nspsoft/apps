<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTaskAttachment extends Model
{
    protected $fillable = [
        'project_task_id',
        'user_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
    ];

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
