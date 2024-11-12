<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use App\Services\CommentService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CommentServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $commentService;
    protected $task;

    /**
     * Set up initial data for the tests.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->commentService = app(CommentService::class);
        $this->task = Task::factory()->create();
    }

    public function test_store_comment_successfully()
    {
        $user = User::factory()->create();

        $role = Role::firstOrCreate(['name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'create comments']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->actingAs($user);

        $task = Task::factory()->create();

        $comment = $this->commentService->storeComment($task, 'This is a test comment.');

        $this->assertDatabaseHas('comments', [
            'comment' => 'This is a test comment.',
            'commentable_type' => Task::class,
            'commentable_id' => $task->id,     
        ]);
    }


    /**
     * Test updating a comment successfully.
     */
    public function test_update_comment_successfully()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'update comment']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->actingAs($user);

        $comment = $this->commentService->storeComment($this->task, 'Initial comment text.');
        $updatedCommentText = 'Updated comment text.';

        $updatedComment = $this->commentService->updateComment($this->task, $comment->id, $updatedCommentText);

        $this->assertEquals($updatedCommentText, $updatedComment->comment);
    }

    /**
     * Test deleting a comment successfully.
     */
    public function test_delete_comment_successfully()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'delete comment']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->actingAs($user);

        $comment = $this->commentService->storeComment($this->task, 'This comment will be deleted.');

        $this->commentService->deleteComment($this->task, $comment->id);

        $this->assertSoftDeleted('comments', [
            'id' => $comment->id,
        ]);
    }

    /**
     * Test force-deleting a soft-deleted comment successfully.
     */
    public function test_force_delete_comment_successfully()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'force delete comment']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->actingAs($user);

        $comment = $this->commentService->storeComment($this->task, 'This comment will be force-deleted.');
        $comment->delete();

        $this->commentService->permanentDeleteComment($this->task, $comment->id);

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    /**
     * Test restoring a soft-deleted comment successfully.
     */
    public function test_restore_deleted_comment_successfully()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'restore comment']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->actingAs($user);

        $comment = $this->commentService->storeComment($this->task, 'This comment will be deleted and restored.');
        $comment->delete();

        $restoredComment = $this->commentService->restoreDeletedComment($this->task, $comment->id);

        $this->assertDatabaseHas('comments', [
            'id' => $restoredComment->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test retrieving soft-deleted comments for a specific task.
     */
    public function test_get_deleted_comments_successfully()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'view deleted comments']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->actingAs($user);

        $comment1 = $this->commentService->storeComment($this->task, 'This is a deleted comment.');
        $comment1->delete();

        $comment2 = $this->commentService->storeComment($this->task, 'This is another deleted comment.');
        $comment2->delete();

        $deletedComments = $this->commentService->getDeletedComments($this->task);

        $this->assertGreaterThanOrEqual(2, $deletedComments->count());
    }
}
