<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskMember extends Model
{
    protected $table = 'task_members';

    protected $fillable = [
        'project_task_id',
        'user_id',
    ];
}
