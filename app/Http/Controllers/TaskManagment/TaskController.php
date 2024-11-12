<?php

namespace App\Http\Controllers\TaskManagment;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest\StoreTaskRequest;
use App\Http\Requests\TaskRequest\AssignTaskRequest;
use App\Http\Requests\TaskRequest\UpdateTaskRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TaskController extends Controller
{

    /**
     * The task service instance.
     *
     * @var \App\Services\TaskService
     */
    protected $taskService;

    /**
     * Create a new controller instance.
     *
     * @param \App\Services\TaskService $taskService
     * @return void
     */
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Store a newly created task.
     *
     * @param StoreTaskRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function store(StoreTaskRequest $request)
    // {
    //         $task = $this->taskService->storeTask($request->validated());

    //         return response()->json($task, 201);
    // }
    public function store(StoreTaskRequest $request)
    {
        try {
            // Call the storeTask method to create the task
            $task = $this->taskService->storeTask($request->validated());

            // Return a JSON response with task data and 201 Created status
            return response()->json($task, 201);  // Make sure the status code is 201
        } catch (BadRequestHttpException $e) {
            // If an error occurs, return an error message with a proper status code
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Display the specified task.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {

            $task = $this->taskService->showTask($id);

            return response()->json($task, 200);

    }
    /**
     * Update the specified task.
     *
     * @param int $id
     * @param UpdateTaskRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateTaskRequest $request,int $id)
    {
        $task = $this->taskService->updateTask($request->validated(),$id);

        return response()->json($task, 200);
    }

    /**
     * Display a listing of tasks based on filters.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {

            $filters = $request->only(['type', 'status', 'priority', 'assigned_to', 'due_date', 'depends_on','created_by']);
            $tasks = $this->taskService->indexTasks($filters);

            return response()->json($tasks, 200);

    }

    /**
     * Generate daily tasks report.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dailyTasksReport(Request $request)
    {

            // return $this->taskService->dailyTasksReport();

                $tasks = $this->taskService->dailyTasksReport();
                return response()->json($tasks);
    }

    /**
     * Get blocked tasks that are overdue.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function blockedTasks(Request $request)
    {

            $today = now()->toDateString();
            $tasks = $this->taskService->blockedTasks($today);

            return response()->json(['data' => $tasks], 200);

    }

    /**
     * Soft delete the specified task along with its dependents.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {

            return $this->taskService->destroyTask($id);

    }

    /**
     * Restore a soft-deleted task along with its dependents.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($id)
    {

            return $this->taskService->restoreTask($id);

    }

    /**
     * Display all soft-deleted tasks.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function trashedTasks()
    {

            return $this->taskService->trashedTasks();

    }

    /**
     * Permanently delete the specified task along with its dependents.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDelete($id)
    {

            return $this->taskService->forceDeleteTask($id);

    }

    /**
     * Assign a task to a user.
     *
     * @param AssignTaskRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assign(AssignTaskRequest $request, $id)
    {

            $userId = $request->validated()['assigned_to'];
            $message = $this->taskService->assignTask($id, $userId);

            return response()->json(['message' => $message], 200);

    }

    /**
     * Reassign a task to a different user.
     *
     * @param AssignTaskRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reassign(AssignTaskRequest $request, $id)
    {
            $userId = $request->validated()['assigned_to'];
            $message = $this->taskService->reassignTask($id, $userId);

            return response()->json(['message' => $message], 200);

    }
      /**
     * Get completed tasks report.
     *
     * @return JsonResponse
     */
    public function getCompletedTasks(): JsonResponse
    {
        $tasks = Task::where('status', 'completed')->get();
        return response()->json(['data' => $tasks], 200);
    }

    /**
     * Get overdue tasks report.
     *
     * @return JsonResponse
     */
    public function getOverdueTasks(): JsonResponse
    {
        $tasks = Task::where('due_date', '<', now())->where('status', '!=', 'completed')->get();
        return response()->json(['data' => $tasks], 200);
    }

    /**
 * Get tasks assigned to a specific user.
 *
 * @param int $userId
 * @return JsonResponse
 */
public function getTasksByUser(int $userId): JsonResponse
{
    $tasks = Task::where('assigned_to', $userId)->get();

    return response()->json(['data' => $tasks], 200);
}
}
