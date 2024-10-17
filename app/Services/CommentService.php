<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Comment;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Service class for handling comment-related operations.
 */
class CommentService
{
    /**
     * Store a new comment for a given task.
     *
     * @param \App\Models\Task $task
     * @param string $commentText
     * @return \App\Models\Comment
     * @throws \Exception
     */
    public function storeComment(Task $task, string $commentText): Comment
    {
        try {
            $comment = $task->comments()->create([
                'comment' => $commentText,
                'user_id' => Auth::id(),
            ]);

            return $comment;
        } catch (Exception $e) {
            Log::error('Failed to store comment: ' . $e->getMessage());
            throw new Exception('Failed to store comment.');
        }
    }
    public function updateComment(Task $task, int $commentId, string $commentText): Comment
    {
        try {
            $comment = $task->comments()->findOrFail($commentId);
            $comment->update(['comment' => $commentText]);
            return $comment;
        } catch (Exception $e) {
            Log::error('Failed to update comment: ' . $e->getMessage());
            throw new Exception('Failed to update comment.');
        }
    }

    public function deleteComment(Task $task, int $commentId): void
    {
        try {
            $comment = $task->comments()->findOrFail($commentId);
            $comment->delete();
        } catch (Exception $e) {
            Log::error('Failed to delete comment: ' . $e->getMessage());
            throw new Exception('Failed to delete comment.');
        }
    }
     /**
     * Permanently delete a comment.
     *
     * @param Task $task
     * @param int $commentId
     * @return void
     * @throws Exception
     */
    public function permanentDeleteComment(Task $task, int $commentId): void
    {
        try {
            $comment = $task->comments()->withTrashed()->findOrFail($commentId);
            $comment->forceDelete();
        } catch (Exception $e) {
            Log::error('Failed to permanently delete comment: ' . $e->getMessage());
            throw new Exception('Failed to permanently delete comment.');
        }
    }

    /**
     * Restore a soft-deleted comment.
     *
     * @param Task $task
     * @param int $commentId
     * @return Comment
     * @throws Exception
     */
    public function restoreDeletedComment(Task $task, int $commentId): Comment
    {
        try {
            $comment = $task->comments()->withTrashed()->findOrFail($commentId);
            $comment->restore();
            return $comment;
        } catch (Exception $e) {
            Log::error('Failed to restore comment: ' . $e->getMessage());
            throw new Exception('Failed to restore comment.');
        }
    }

    /**
     * Get all soft-deleted comments for a specific task.
     *
     * @param Task $task
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDeletedComments(Task $task)
    {
        return $task->comments()->onlyTrashed()->get();
    }
}
