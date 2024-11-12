<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TaskServiceTest extends TestCase
{

    // use RefreshDatabase;

    // Use DatabaseTransactions to roll back changes after each test
    use DatabaseTransactions;

    protected $taskService;


    protected $task;

     /**
     * Set up initial data for the tests.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->taskService = app(TaskService::class);
        $this->task=[
            'title'=>"Test Task",
            'description'=>"teeest",
            'type'=>"Improvement",
            'status'=>"In Progress",
            'priority'=>"High",
            'due_date'=> now()->toDateString(),

        ];

    }
    /**
     * Test creating a task successfully.
     */
    public function test_create_a_task_successfully(){
            // Create or retrieve the test user
            $user = User::factory()->create();

            // Assign the necessary role or permission
            $role = Role::firstOrCreate(['name' => 'admin']);
            $permission = Permission::firstOrCreate(['name' => 'store task']);
            $role->givePermissionTo($permission);
            $user->assignRole($role);

            // Acting as the authorized user
            $this->actingAs($user);

            $create_task=$this->taskService->storeTask($this->task);
            $this->assertDatabaseHas('tasks',['title'=>"Test Task"]);
    }
    /**
     * Test displaying a task successfully.
     */
    public function test_show_task_successfully()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'view task']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->actingAs($user);

        $task = Task::factory()->create();
        $result = $this->taskService->showTask($task->id);

        $this->assertEquals($task->id, $result->id);
    }
    /**
     * Test retrieving a list of tasks successfully.
     */
    public function test_index_tasks_successfully()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'view tasks']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->actingAs($user);

        $filters = ['status' => 'In Progress'];
        $tasks = $this->taskService->indexTasks($filters);

        $this->assertGreaterThanOrEqual(1, $tasks->count());
    }
    /**
     * Test generating a daily tasks report.
     */
    public function test_daily_tasks_report_successfully()
{
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'admin']);
    $permission = Permission::firstOrCreate(['name' => 'daily tasks']);
    $role->givePermissionTo($permission);
    $user->assignRole($role);

    $this->actingAs($user);

    // $response = $this->taskService->dailyTasksReport();
    // $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);

    // $tasks = $response->getData(true); // Get data as an array
    // $this->assertIsArray($tasks);
    Task::factory()->create(['due_date' => now()]);

    $tasks = $this->taskService->dailyTasksReport();

    $this->assertIsIterable($tasks);
    $this->assertNotEmpty($tasks);
}
    /**
         * Test retrieving blocked tasks successfully.
         */
    public function test_blocked_tasks_successfully()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'view blocked tasks']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->actingAs($user);

        Task::create([
            'title' => 'Blocked Task',
            'status' => 'Blocked',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Blocked Task',
            'status' => 'Blocked',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $today = now()->toDateString();
        $tasks = $this->taskService->blockedTasks($today);
        // dd($tasks);
        $this->assertNotEmpty($tasks);
    }
  /**
     * Test deleting a task successfully.
     */
    public function test_destroy_task_successfully()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'delete task']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->actingAs($user);

        $task = Task::factory()->create();
        $response = $this->taskService->destroyTask($task->id);

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    /**
     * Test restoring a deleted task successfully.
     */
    public function test_restore_task_successfully()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'restore task']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->actingAs($user);

        $task = Task::factory()->create();
        $task->delete();

        $response = $this->taskService->restoreTask($task->id);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'deleted_at' => null]);
    }
/**
     * Test retrieving trashed tasks.
     */
    public function test_trashed_tasks_successfully()
{
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'admin']);
    $permission = Permission::firstOrCreate(['name' => 'view trashed tasks']);
    $role->givePermissionTo($permission);
    $user->assignRole($role);

    $this->actingAs($user);

    $task = Task::factory()->create();
    $task->delete();

    $response = $this->taskService->trashedTasks();
    $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);

    $trashedTasks = $response->getData(true); // Get data as an array
    $this->assertGreaterThanOrEqual(1, count($trashedTasks['trashed_tasks']));
    }
    /**
     * Test force-deleting a task.
     */
    public function test_force_delete_task_successfully()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'force delete task']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->actingAs($user);

        $task = Task::factory()->create();
        $task->delete();

        $response = $this->taskService->forceDeleteTask($task->id);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
    /**
     * Test assigning a task to a user successfully.
     */
    public function test_assign_task_successfully()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'assign task']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->actingAs($user);

        $task = Task::factory()->create();
        $assignee = User::factory()->create();

        $response = $this->taskService->assignTask($task->id, $assignee->id);

        $this->assertEquals($assignee->id, $task->fresh()->assigned_to);
    }
    /**
     * Test updating a task's attributes successfully.
     */
    public function test_update_task_successfully()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'update task']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->actingAs($user);

        $task = Task::factory()->create();
        $updateData = [
            'title' => 'Updated Task Title',
            'description' => 'Updated Task Description',
            'priority' => 'Low',
        ];

        $response = $this->taskService->updateTask($updateData, $task->id);

        $this->assertEquals('Updated Task Title', $task->fresh()->title);
        $this->assertEquals('Updated Task Description', $task->fresh()->description);
        $this->assertEquals('Low', $task->fresh()->priority);
    }




}
