<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');

    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        return $user->hasRole('admin') || ($user->id === $task->created_by && $user->hasRole('manager')) || $user->id === $task->assigned_to;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin') || ($user->hasRole('manager'));

    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        return $user->hasRole('admin') || ($user->hasRole('manager') && $user->id === $task->created_by);

    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->hasRole('admin') || ($user->hasRole('manager') && $user->id === $task->created_by);

    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        return $user->hasRole('admin') || ($user->hasRole('manager') && $user->id === $task->created_by);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return $user->hasRole('admin') || ($user->hasRole('manager') && $user->id === $task->created_by);
    }
    /**
     * Determine whether the user can  Generate daily tasks report.
     */
    public function viewDailyTasksReport(User $user)
    {
        return $user->hasRole('admin') || $user->hasRole('manager');
    }
    /**
     * Determine whether the user can Get blocked tasks that are overdue.
     */
    public function viewBlockedTasks(User $user)
    {
        return $user->hasRole('admin') || $user->hasRole('manager');
    }
    /**
     * Determine whether the user can Display all soft-deleted tasks.
     */
    public function viewTrashedTasks(User $user)
    {
        return $user->hasRole('admin') || $user->hasRole('manager')   ; }
    /**
     * Determine whether the user can Assign a task to a user.
     */
    public function assignTask(User $user)
    {
        return $user->hasRole('admin') || $user->hasRole('manager');
    }

}
