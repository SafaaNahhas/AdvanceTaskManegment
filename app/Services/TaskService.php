<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\TaskResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TaskService
{
    use AuthorizesRequests;

    /**
     * Store a newly created task in storage.
     *
     * @param array $data
     * @return TaskResource
     */
    public function storeTask(array $data)
    {
        $this->authorize('create', Task::class);

        try {
            // Begin Transaction
            DB::beginTransaction();

            // Create Task
            $task = Task::create([
                'title'        => $data['title'],
                'description'  => $data['description'] ?? null,
                'type'         => $data['type'],
                'priority'     => $data['priority'],
                'due_date'     => $data['due_date'] ,
                'created_by' => Auth::id(),

            ]);

            // Attach Dependencies if any
            if (isset($data['dependencies'])) {
                $task->dependencies()->attach($data['dependencies']);
            }

            // Commit Transaction
            DB::commit();

            // Clear relevant cache
            $this->clearCache();

            return new TaskResource($task->load([ 'dependencies','creator']));
        } catch (\Exception $e) {
            // Rollback Transaction
            DB::rollBack();

            // Log Error
            Log::error('Error creating task: ' . $e->getMessage());

            // Re-throw Exception
            throw new BadRequestHttpException('Failed to create task: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified task.
     *
     * @param int $id
     * @return TaskResource
     * @throws \Exception
     */
    public function showTask(int $id)
    {

        try {
            $cacheKey = 'task_' . $id;

            $task = Cache::remember($cacheKey, 3600, function () use ($id) {
                return Task::with([
                    'comments:id,commentable_id,commentable_type,comment',
                    'attachments:id,file_type,attachable_id,attachable_type',
                    'assignedUser:id,name',
                    'dependencies:id,title',
                    'dependents:id,title',
                    'creator:id,name',
                ])->find($id);
            });

            if (!$task) {
                throw new ModelNotFoundException('Task not found');

            }
            $this->authorize('view', $task);
            return new TaskResource($task);
        } catch (\Exception $e) {
            Log::error('Error fetching task: ' . $e->getMessage());
        throw new BadRequestHttpException('Failed to show Task: ' . $e->getMessage());
        }
    }

    /**
     * Get a list of tasks based on filters.
     *
     * @param array $filters
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function indexTasks(array $filters)
    {
        $this->authorize('viewAny', Task::class);
        try {
            $cacheKey = 'tasks_' . md5(serialize($filters));
            $cacheKeys = Cache::get('cache_keys', []);

            if (!in_array($cacheKey, $cacheKeys)) {
                $cacheKeys[] = $cacheKey;
                Cache::put('cache_keys', $cacheKeys, 3600);
            }

            $tasks = Cache::remember($cacheKey, 3600, function () use ($filters) {
                $query = Task::query();

                // Apply Filters
                if (isset($filters['type'])) {
                    $query->where('type', $filters['type']);
                }

                if (isset($filters['status'])) {
                    $query->where('status', $filters['status']);
                }

                if (isset($filters['priority'])) {
                    $query->where('priority', $filters['priority']);
                }

                if (isset($filters['assigned_to'])) {
                    $query->where('assigned_to', $filters['assigned_to']);
                }

                if (isset($filters['due_date'])) {
                    $query->whereDate('due_date', $filters['due_date']);
                }

                if (isset($filters['depends_on'])) {
                    if ($filters['depends_on'] === 'null') {
                        $query->whereDoesntHave('dependencies');
                    } else {
                        $query->whereHas('dependencies', function ($q) use ($filters) {
                            $q->where('id', $filters['depends_on']);
                        });
                    }
                }

                return $query->with(['assignedUser', 'dependencies', 'creator'])->get();
            });

            return TaskResource::collection($tasks);
        } catch (\Exception $e) {
            Log::error('Error fetching tasks: ' . $e->getMessage());
        throw new BadRequestHttpException('Failed to show Tasks: ' . $e->getMessage());
        }
    }

    /**
     * Generate daily tasks report.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dailyTasksReport()
    {
        $this->authorize('viewDailyTasksReport', Task::class);

        try {
            $today = now()->toDateString();

            $tasks = Task::whereDate('created_at', $today)->get();

            return response()->json($tasks);
        } catch (\Exception $e) {
            Log::error('Error generating daily tasks report: ' . $e->getMessage());
        throw new BadRequestHttpException('Failed to show dailyTasksReport: ' . $e->getMessage());
        }
    }

    /**
     * Get blocked tasks that are overdue.
     *
     * @param string $today
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
public function blockedTasks(string $today)
{
    $this->authorize('viewBlockedTasks', Task::class);
    try {
        $cacheKey = 'blocked_tasks_' . $today;

        $tasks = Cache::remember($cacheKey, 3600, function () use ($today) {
            $query = Task::where('status', 'Blocked')
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', $today)
                        ->with(['dependencies:id,title']);

            // سجّل الاستعلام
            Log::info('Query for blocked tasks: ' . $query->toSql());

            return $query->get();
        });

        return TaskResource::collection($tasks);
    } catch (\Exception $e) {
        Log::error('Error fetching blocked tasks: ' . $e->getMessage());
        throw new BadRequestHttpException('Failed to show blockedtasks: ' . $e->getMessage());
    }
}


    /**
     * Soft delete the specified task along with its dependents and comment and attachment.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */

    public function destroyTask(int $id)
    {


        try {
            DB::beginTransaction();

            $task = Task::find($id);

            if (!$task) {
                throw new ModelNotFoundException('Task not found');
            }
            $this->authorize('delete', $task);
            $task->comments()->delete();
            $task->attachments()->delete();

            if ($task->dependents()->exists()) {
                foreach ($task->dependents as $dependentTask) {
                    $dependentTask->comments()->delete();
                    $dependentTask->attachments()->delete();
                    $dependentTask->delete();
                }
            }
            $task->delete();

            DB::commit();

            $this->clearCache();

            return response()->json(['message' => 'Task deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error deleting task: ' . $e->getMessage());
        throw new BadRequestHttpException('Failed to delete task: ' . $e->getMessage());
        }
    }
    /**
     * Restore a soft-deleted task along with its dependents and comment and attachment.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
        public function restoreTask(int $id)
    {


        try {
            DB::beginTransaction();

            $task = Task::withTrashed()->find($id);

            if (!$task) {
                throw new ModelNotFoundException('Task not found');
            }
            $this->authorize('restore', $task);
            $task->restore();

            $task->comments()->withTrashed()->restore();
            $task->attachments()->withTrashed()->restore();

            if ($task->dependents()->withTrashed()->exists()) {
                foreach ($task->dependents()->withTrashed()->get() as $dependentTask) {
                    $dependentTask->restore();
                    $dependentTask->comments()->withTrashed()->restore();
                    $dependentTask->attachments()->withTrashed()->restore();
                }
            }

            DB::commit();

            $this->clearCache();

            return response()->json(['message' => 'Task restored successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error restoring task: ' . $e->getMessage());
        throw new BadRequestHttpException('Failed to restored task: ' . $e->getMessage());
        }
    }
    /**
     * Display all soft-deleted tasks.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function trashedTasks()
    {
        $this->authorize('viewTrashedTasks', Task::class);
        try {
            $trashedTasks = Task::onlyTrashed()->with(['comments', 'attachments', 'dependents.comments', 'dependents.attachments'])->get();

            return response()->json(['trashed_tasks' => TaskResource::collection($trashedTasks)], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching trashed tasks: ' . $e->getMessage());
        throw new BadRequestHttpException('Failed to show trashedTasks: ' . $e->getMessage());
        }
    }
    /**
     * Force delete the specified task along with its dependents and comment and attachment.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */

    public function forceDeleteTask(int $id)
    {

        try {
            DB::beginTransaction();

            $task = Task::withTrashed()->find($id);

            if (!$task) {
                throw new ModelNotFoundException('Task not found');
            }
            $this->authorize('forceDelete', $task);
            $task->comments()->withTrashed()->forceDelete();
            $task->attachments()->withTrashed()->forceDelete();

            if ($task->dependents()->withTrashed()->exists()) {
                foreach ($task->dependents()->withTrashed()->get() as $dependentTask) {
                    $dependentTask->comments()->withTrashed()->forceDelete();
                    $dependentTask->attachments()->withTrashed()->forceDelete();
                    $dependentTask->forceDelete();
                }
            }

            $task->forceDelete();

            DB::commit();

            $this->clearCache();

            return response()->json(['message' => 'Task permanently deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error force deleting task: ' . $e->getMessage());
        throw new BadRequestHttpException('Failed to forceDeleteTask: ' . $e->getMessage());
        }
    }
    /**
     * Assign a task to a user.
     *
     * @param int $id
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
      public function assignTask(int $id, int $userId)
    {


        try {
              // Find the task first
              $task = Task::find($id);
              if (!$task) {
                  throw new ModelNotFoundException('Task not found');
              }

              $this->authorize('assignTask', $task);

            $task->assigned_to = $userId;
            $task->save();


            // Clear relevant cache
            $this->clearCache();

            return response()->json(['message' => 'Task assigned successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error assigning task: ' . $e->getMessage());
            throw new BadRequestHttpException('Failed to assign task: ' . $e->getMessage());
        }
    }

    /**
     * Reassign a task to a different user.
     *
     * @param int $id
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */

    public function reassignTask(int $id, int $userId)
    {

        try {
            // Find the task first
            $task = Task::find($id);
            if (!$task) {
                throw new ModelNotFoundException('Task not found');
            }
            $this->authorize('assignTask', $task);
            // Check the previous assigned user (for logging or other purposes, optional)
            $previousUserId = $task->assigned_to;

            // Update the task with the new assigned user, effectively unlinking the previous user
            $task->assigned_to = $userId;
            $task->save();

            // Clear relevant cache
            $this->clearCache();

            // Return success response
            return response()->json(['message' => 'Task reassigned successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error reassigning task: ' . $e->getMessage());
            throw new BadRequestHttpException('Failed to reassignTask: ' . $e->getMessage());
        }
    }
    /**
     * Update the specified task with new data.
     * The method uses database transactions to ensure atomicity of the operation.
     * If any exception occurs, the transaction is rolled back and an error is logged.
     *
     * @param array $data The new data for updating the task (title, description, etc.)
     * @param int $id The ID of the task to update
     * @return TaskResource The updated task resource with the associated assigned user and dependencies
     *
     * @throws ModelNotFoundException If the task is not found
     * @throws BadRequestHttpException If the update fails due to any other error
     */

    public function updateTask(array $data, int $id)
    {
        // Find the task first before authorizing
        $task = Task::find($id);
        if (!$task) {
            throw new ModelNotFoundException('Task not found');
        }

        // Authorize based on the found task instance
        $this->authorize('update', $task);

        try {
            // Begin Transaction
            DB::beginTransaction();

            // Update task attributes
            $task->update([
                'title'       => $data['title'],
                'description' => $data['description'] ?? $task->description,
                'type'        => $data['type'] ?? $task->type,
                'priority'    => $data['priority'] ?? $task->priority,
                'due_date'    => $data['due_date'] ?? $task->due_date,
            ]);

            // Check for dependencies
            if (isset($data['dependencies'])) {
                // Ensure the task does not depend on itself
                if (in_array($id, $data['dependencies'])) {
                    throw new \Exception('Task cannot depend on itself.');
                }

                // Check for circular dependencies
                foreach ($data['dependencies'] as $dependencyId) {
                    if ($this->hasCircularDependency($id, $dependencyId)) {
                        throw new \Exception('Circular dependency detected.');
                    }
                }

                // Sync dependencies
                $task->dependencies()->sync($data['dependencies']);
            }

            // Commit Transaction
            DB::commit();

            // Clear relevant cache
            $this->clearCache();

            return new TaskResource($task->load(['assignedUser', 'dependencies']));
        } catch (\Exception $e) {
            // Rollback Transaction
            DB::rollBack();

            // Log Error
            Log::error('Error updating task: ' . $e->getMessage());

            // Re-throw Exception
            throw new BadRequestHttpException('Failed to update task: ' . $e->getMessage());
        }
    }

    /**
     * Check if there is a circular dependency.
     *
     * @param int $taskId
     * @param int $dependencyId
     * @return bool
     */
    protected function hasCircularDependency(int $taskId, int $dependencyId): bool
    {
        $task = Task::find($dependencyId);

        // If the task has no dependencies, no circular dependency
        if (!$task || $task->dependencies->isEmpty()) {
            return false;
        }

        // Recursively check if any dependency leads back to the original task
        foreach ($task->dependencies as $dependency) {
            if ($dependency->id == $taskId || $this->hasCircularDependency($taskId, $dependency->id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clear all relevant cache keys.
     *
     * @return void
     */
    protected function clearCache()
    {

        $cacheKeys = Cache::get('cache_keys', []);
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        Cache::forget('cache_keys');

    }
}
