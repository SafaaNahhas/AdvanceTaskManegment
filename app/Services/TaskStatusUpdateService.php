<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskStatusUpdate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Jobs\GenerateDailyTasksReport;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Class TaskService
 * @package App\Services\TaskManagement
 */
class TaskStatusUpdateService
{

    /**
     * Update the status of the given task and its dependents.
     *
     * @param Task $task
     * @param string $newStatus
     *
     * @throws \Exception
     */
    public function updateTaskStatus(Task $task, string $newStatus)
    {
        $oldStatus = $task->status;
        $user = Auth::user();

        if (!($user->hasRole('admin') || ($user->hasRole('manager') && $user->id === $task->created_by))) {
            throw new UnauthorizedHttpException('Unauthorized');

        }
        // Validate the status change
        if ($newStatus === 'Completed' && $oldStatus !== 'In Progress') {
            throw new \Exception('Cannot change status to Completed unless current status is In Progress');
        }

        if ($newStatus === 'In Progress' && $oldStatus === 'Completed') {
            throw new \Exception('Cannot change status to In Progress after it has been marked as Completed');
        }

        if (in_array($newStatus, ['In Progress', 'Completed'])) {
            $blockedDependencies = $task->dependencies()->where('status', '!=', 'Completed')->count();
            if ($blockedDependencies > 0) {
                throw new \Exception('Cannot change task status due to incomplete dependencies');
            }
        }

        // Update the task status
        $task->status = $newStatus;
        $task->save();

        // Log the status update
        TaskStatusUpdate::create([
            'task_id' => $task->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'updated_by' => Auth::id(),
        ]);

        // Handle dependent tasks
        $this->handleDependentTasks($task, $newStatus);

        // Update cache for the task
        Cache::forget('task_' . $task->id);
        
        GenerateDailyTasksReport::dispatch($user);

    }

    /**
     * Handle the status changes for dependent tasks.
     *
     * @param Task $task
     * @param string $newStatus
     * @return void
     */
    protected function handleDependentTasks(Task $task, string $newStatus): void
    {
        $dependents = $task->dependents()->get();

        if ($newStatus === 'Completed') {
            foreach ($dependents as $dependentTask) {
                $pendingDependencies = $dependentTask->dependencies()
                    ->where('status', '!=', 'Completed')
                    ->count();

                if ($pendingDependencies === 0 && $dependentTask->status === 'Blocked') {
                    $dependentTask->status = 'Open';
                    $dependentTask->save();

                    // Update cache for dependent task
                    Cache::forget('task_' . $dependentTask->id);

                    TaskStatusUpdate::create([
                        'task_id' => $dependentTask->id,
                        'old_status' => 'Blocked',
                        'new_status' => 'Open',
                        'updated_by' => Auth::id(),
                    ]);
                }
            }
        }

        if ($newStatus === 'In Progress') {
            foreach ($dependents as $dependentTask) {
                $dependentTask->status = 'Blocked';
                $dependentTask->save();

                // Update cache for dependent task
                Cache::forget('task_' . $dependentTask->id);

                TaskStatusUpdate::create([
                    'task_id' => $dependentTask->id,
                    'old_status' => $dependentTask->status,
                    'new_status' => 'Blocked',
                    'updated_by' => Auth::id(),
                ]);
            }
        }
    }
}
