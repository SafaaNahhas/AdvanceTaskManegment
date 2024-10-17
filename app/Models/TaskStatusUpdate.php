<?php

namespace App\Models;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskStatusUpdate extends Model
{
    use HasFactory;
    protected $fillable = [
        'task_id',
        'old_status',
        'new_status',
        'updated_by',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

}
