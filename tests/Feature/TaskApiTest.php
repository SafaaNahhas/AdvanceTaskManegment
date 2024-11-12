<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TaskApiTest extends TestCase
{
    // use RefreshDatabase;
       // Use DatabaseTransactions to roll back changes after each test
       use DatabaseTransactions;
    protected $user;
    /**
     * Set up the test environment.
     *
     * Create a user with assigned role and permissions.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and assign a role with permissions
        $this->user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $permissions = [
            'store task', 'view tasks', 'destroy tasks',
            'assign task', 'restore tasks', 'forceDelete tasks',
            'update task', 'daily tasks', 'view task', 'reassign task'
        ];

        foreach ($permissions as $perm) {
            $permission = Permission::firstOrCreate(['name' => $perm]);
            $role->givePermissionTo($permission);
        }

        $this->user->assignRole($role);
    }

    /**
     * Test the ability to create a task.
     *
     * @return void
     */
    public function test_can_create_task()
    {
        $this->actingAs($this->user);

        $taskData = [
            'title' => 'API Test Task',
            'description' => 'Task description for API testing',
            'type' => 'Improvement',
            'status' => 'In Progress',
            'priority' => 'High',
            'due_date' => now()->addDays(5)->toDateString(),
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', ['title' => 'API Test Task']);
    }

    /**
     * Test the ability to view tasks.
     *
     * @return void
     */
    public function test_can_view_tasks()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'view task']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
        // Act as the user
        $this->actingAs($user, 'api');
        // Create 3 tasks associated with the user
        Task::factory()->count(3)->create(['created_by' => $user->id]);
        // Retrieve the tasks
        $response = $this->getJson('/api/tasks?created_by=' . $user->id);

        $response->assertStatus(200);
         // Show the API response for debugging
        $response->dump();
        // Check that 3 tasks are returned
        $response->assertJsonCount(3);
    }





    /**
     * Test the ability to view a single task.
     *
     * @return void
     */    public function test_can_view_single_task()
    {
        $this->actingAs($this->user);

        $task = Task::factory()->create(['title' => 'View Single Task Test']);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'View Single Task Test']);
    }

    /**
     * Test the ability to soft delete a task.
     *
     * @return void
     */
    public function test_can_soft_delete_task()
    {
        $this->actingAs($this->user);

        $task = Task::factory()->create();

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }
    /**
     * Test the ability to restore a soft deleted task.
     *
     * @return void
     */
    public function test_can_restore_task()
    {
        $this->actingAs($this->user);

        $task = Task::factory()->create();
        $task->delete();

        $response = $this->postJson("/api/tasks/restore/{$task->id}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'deleted_at' => null]);
    }

    /**
     * Test the ability to force delete a task.
     *
     * @return void
     */    public function test_can_force_delete_task()
    {
        $this->actingAs($this->user);

        $task = Task::factory()->create();
        $task->delete();

        $response = $this->deleteJson("/api/tasks/force-delete/{$task->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    /**
     * Test the ability to update a task.
     *
     * @return void
     */    public function test_can_update_task()
    {
        $this->actingAs($this->user);

        $task = Task::factory()->create(['title' => 'Original Title']);

        $updatedData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'priority' => 'Low'
        ];

        $response = $this->putJson("/api/tasks/{$task->id}", $updatedData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'priority' => 'Low'
        ]);
    }

    /**
     * Test the ability to view the daily tasks report.
     *
     * @return void
     */    public function test_can_view_daily_tasks_report()
    {
        $this->actingAs($this->user);

        Task::factory()->create(['created_at' => now()]);
        Task::factory()->create(['created_at' => now()]);

        $response = $this->getJson('/api/reports/daily-tasks');

        $response->assertStatus(200);
        $this->assertIsArray($response->json());
    }


    public function test_can_view_blocked_tasks()
    {
        $this->actingAs($this->user);

        Task::factory()->count(2)->state(['status' => 'Blocked'])->create();

        $response = $this->getJson('/api/tasks/blocked');

        $response->assertStatus(200);

    $tasks = $response->json('data');

    $this->assertNotEmpty($tasks);

    foreach ($tasks as $task) {
        $this->assertEquals('Blocked', $task['status']);
    }
    }




public function test_cannot_reassign_task_due_to_insufficient_time()
{
    $this->actingAs($this->user);

    $task = Task::factory()->create([
        'priority' => 'High',
        'due_date' => now()->addDays(2),
    ]);
    $newUser = User::factory()->create();

    $response = $this->putJson("/api/tasks/{$task->id}/reassign", [
        'assigned_to' => $newUser->id,
    ]);

    $response->assertStatus(400); // تأكد من رمز الاستجابة
    $response->assertJson([
        'error' => 'Failed to reassign task: Cannot reassign task due to insufficient time remaining.',
    ]);
}


public function test_can_assign_task()
{
    $this->actingAs($this->user);

    $task = Task::factory()->create();
    $assignee = User::factory()->create();

    $response = $this->postJson("/api/tasks/{$task->id}/assign", [
        'assigned_to' => $assignee->id,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'assigned_to' => $assignee->id,
    ]);
}
public function test_can_view_completed_tasks_report()
{
    $this->actingAs($this->user);
    // Create two completed tasks
    Task::factory()->create(['status' => 'Completed']);
    Task::factory()->create(['status' => 'Completed']);

    $response = $this->getJson('/api/reports/completed-tasks');
    $response->assertStatus(200);

      $tasks = $response->json('data');

      $this->assertNotEmpty($tasks);

      foreach ($tasks as $task) {
          $this->assertEquals('Completed', $task['status']);
      }
}

public function test_can_view_overdue_tasks_report()
{
    $this->actingAs($this->user);

    Task::factory()->count(2)->state(['due_date' => now()->subDays(1), 'status' => 'In Progress'])->create();

    $response = $this->getJson('/api/reports/overdue-tasks');

    $response->assertStatus(200);
    $tasks = $response->json('data');

    foreach ($tasks as $task) {
        $this->assertTrue(strtotime($task['due_date']) < strtotime(now()));
        $this->assertNotEquals('completed', $task['status']);
    }
}
public function test_can_view_tasks_by_user_report()
{
    $this->actingAs($this->user);

    $assignedUser = User::factory()->create();
    Task::factory()->create(['assigned_to' => $assignedUser->id, 'status' => 'Completed', 'created_at' => now()]);
    Task::factory()->create(['assigned_to' => $assignedUser->id, 'status' => 'Open', 'created_at' => now()]);
    Task::factory()->create(['assigned_to' => $assignedUser->id, 'status' => 'Blocked', 'created_at' => now()]);


    $response = $this->getJson("/api/reports/tasks-by-user/{$assignedUser->id}");

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
    }

}
