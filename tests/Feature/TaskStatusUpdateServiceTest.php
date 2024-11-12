<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Services\TaskStatusUpdateService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TaskStatusUpdateServiceTest extends TestCase
{
   // Use DatabaseTransactions to roll back changes after each test
   use DatabaseTransactions;

   protected $taskStatusUpdateService;

   protected $task;

   /**
    * Set up initial data for the tests.
    */
   protected function setUp(): void
   {
       parent::setUp();
       $this->taskStatusUpdateService = app(TaskStatusUpdateService::class);
       $this->task = Task::create([
           'title' => "Test Task",
           'description' => "Test Description",
           'type' => "Improvement",
           'status' => "In Progress",
           'priority' => "High",
           'due_date' => now()->toDateString(),
           'created_by' => 1,
       ]);
   }

   /**
    * Test updating task status successfully.
    */
   public function test_update_task_status_successfully()
   {
       // Create a user and assign roles
       $user = User::factory()->create();
       $role = Role::firstOrCreate(['name' => 'admin']);
       $permission = Permission::firstOrCreate(['name' => 'update task status']);
       $role->givePermissionTo($permission);
       $user->assignRole($role);

       // Acting as the authorized user
       $this->actingAs($user);

       // Update task status
       $newStatus = 'Completed';
       $this->taskStatusUpdateService->updateTaskStatus($this->task, $newStatus);

       $this->assertEquals($newStatus, $this->task->fresh()->status);
   }

   /**
    * Test updating task status with invalid status transition.
    */
   public function test_update_task_status_invalid_transition()
   {
       // Create a user and assign roles
       $user = User::factory()->create();
       $role = Role::firstOrCreate(['name' => 'admin']);
       $permission = Permission::firstOrCreate(['name' => 'update task status']);
       $role->givePermissionTo($permission);
       $user->assignRole($role);

       // Acting as the authorized user
       $this->actingAs($user);

       // Set status to Completed and try to change to In Progress
       $this->task->update(['status' => 'Completed']);

       $this->expectException(\Exception::class);
       $this->expectExceptionMessage('Cannot change status to In Progress after it has been marked as Completed');

       $this->taskStatusUpdateService->updateTaskStatus($this->task, 'In Progress');
   }

   /**
    * Test updating task status with incomplete dependencies.
    */
   public function test_update_task_status_with_incomplete_dependencies()
   {
       // Create a user and assign roles
       $user = User::factory()->create();
       $role = Role::firstOrCreate(['name' => 'admin']);
       $permission = Permission::firstOrCreate(['name' => 'update task status']);
       $role->givePermissionTo($permission);
       $user->assignRole($role);

       // Acting as the authorized user
       $this->actingAs($user);

       // Simulate a dependency that is not completed
       $dependentTask = Task::create([
           'title' => "Dependent Task",
           'status' => "In Progress",
           'priority' => "High",
           'due_date' => now()->toDateString(),
           'created_by' => 1,
       ]);
       $this->task->dependencies()->attach($dependentTask->id);

       // Try updating task status to "In Progress"
       $this->expectException(\Exception::class);
       $this->expectExceptionMessage('Cannot change task status due to incomplete dependencies');

       $this->taskStatusUpdateService->updateTaskStatus($this->task, 'In Progress');
   }

   /**
    * Test logging task status updates.
    */
   public function test_task_status_update_log()
   {
       // Create a user and assign roles
       $user = User::factory()->create();
       $role = Role::firstOrCreate(['name' => 'admin']);
       $permission = Permission::firstOrCreate(['name' => 'update task status']);
       $role->givePermissionTo($permission);
       $user->assignRole($role);

       // Acting as the authorized user
       $this->actingAs($user);

       // Update task status
       $newStatus = 'Completed';
       $this->taskStatusUpdateService->updateTaskStatus($this->task, $newStatus);

       // Check if the log entry exists
       $this->assertDatabaseHas('task_status_updates', [
           'task_id' => $this->task->id,
           'old_status' => 'In Progress',
           'new_status' => 'Completed',
           'updated_by' => $user->id,
       ]);
   }

   /**
    * Test unauthorized status update attempt.
    */
   public function test_unauthorized_task_status_update()
   {
       // Create a user without admin or manager role
       $user = User::factory()->create();

       // Acting as the unauthorized user
       $this->actingAs($user);

       // Try updating task status
       $this->expectException(\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException::class);

       $this->taskStatusUpdateService->updateTaskStatus($this->task, 'Completed');
   }
}
