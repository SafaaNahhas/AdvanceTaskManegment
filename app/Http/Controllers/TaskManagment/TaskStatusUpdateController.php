<?php

namespace App\Http\Controllers\TaskManagment;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Models\TaskStatusUpdate;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Services\TaskStatusUpdateService;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\TaskRequest\UpdateTaskStatusRequest;

class TaskStatusUpdateController extends Controller
{

    protected $taskService;

    public function __construct(TaskStatusUpdateService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Update the status of a specific task.
     *
     * @param UpdateTaskStatusRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(UpdateTaskStatusRequest $request, $id)
    {

            // Find the task
            $task = Task::find($id);

            if (!$task) {
                return response()->json(['error' => 'Task not found'], 404);
            }

            // Update the task status using the service
            $this->taskService->updateTaskStatus($task, $request->status);

            return response()->json(['message' => 'Task status updated successfully']);

    }
}
