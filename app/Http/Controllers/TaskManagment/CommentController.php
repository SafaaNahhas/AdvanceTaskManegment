<?php

namespace App\Http\Controllers\TaskManagment;

use Exception;
use App\Models\Task;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\CommentRequest\StoreCommentRequest;
use App\Http\Requests\CommentRequest\UpdateCommentRequest;

class CommentController extends Controller
{
//    public function store(Request $request, $taskId)
//    {
//        $validator = Validator::make($request->all(), [
//            'comment' => 'required|string',
//        ]);

//        if ($validator->fails()) {
//            return response()->json($validator->errors(), 422);
//        }

//        $task = Task::find($taskId);

//        if (!$task) {
//            return response()->json(['error' => 'المهمة غير موجودة'], 404);
//        }

//        $comment = $task->comments()->create([
//            'comment'  => $request->comment,
//            'user_id'  => Auth::id(),
//        ]);

//        return response()->json($comment, 201);
//    }
 /**
     * The comment service instance.
     *
     * @var \App\Services\CommentService
     */
    protected $commentService;

    /**
     * Create a new controller instance.
     *
     * @param CommentService $commentService
     * @return void
     */
    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Store a newly created comment for a specific task.
     *
     * @param StoreCommentRequest $request
     * @param int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCommentRequest $request, int $taskId): JsonResponse
    {

            $task = Task::find($taskId);

            if (!$task) {
                return response()->json(['error' => 'Task not found'], 404);
            }

            $commentText = $request->input('comment');

            $comment = $this->commentService->storeComment($task, $commentText);

            return response()->json(['data' => $comment], 201);

    }

    /**
     * Display a listing of comments for a specific task.
     *
     * @param int $taskId
     * @return JsonResponse
     */
    public function index(int $taskId): JsonResponse
    {
        try {
            $task = Task::findOrFail($taskId);
            $comments = $task->comments; // Assuming a one-to-many relationship
            return response()->json(['data' => $comments], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified comment.
     *
     * @param UpdateCommentRequest $request
     * @param int $taskId
     * @param int $commentId
     * @return JsonResponse
     */
    public function update(UpdateCommentRequest $request, int $taskId, int $commentId): JsonResponse
    {
        try {
            $task = Task::findOrFail($taskId);
            $comment = $this->commentService->updateComment($task, $commentId, $request->input('comment'));
            return response()->json(['data' => $comment], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified comment.
     *
     * @param int $taskId
     * @param int $commentId
     * @return JsonResponse
     */
    public function destroy(int $taskId, int $commentId): JsonResponse
    {
        try {
            $task = Task::findOrFail($taskId);
            $this->commentService->deleteComment($task, $commentId);
            return response()->json(['message' => 'Comment deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
     /**
     * Permanently delete a specific comment.
     *
     * @param int $taskId
     * @param int $commentId
     * @return JsonResponse
     */
    public function permanentDelete(int $taskId, int $commentId): JsonResponse
    {
        try {
            $task = Task::findOrFail($taskId);
            $this->commentService->permanentDeleteComment($task, $commentId);
            return response()->json(['message' => 'Comment permanently deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Restore a soft-deleted comment.
     *
     * @param int $taskId
     * @param int $commentId
     * @return JsonResponse
     */
    public function restore(int $taskId, int $commentId): JsonResponse
    {
        try {
            $task = Task::findOrFail($taskId);
            $comment = $this->commentService->restoreDeletedComment($task, $commentId);
            return response()->json(['data' => $comment], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display all soft-deleted comments for a specific task.
     *
     * @param int $taskId
     * @return JsonResponse
     */
    public function showDeleted(int $taskId): JsonResponse
    {
        try {
            $task = Task::findOrFail($taskId);
            $deletedComments = $this->commentService->getDeletedComments($task);
            return response()->json(['data' => $deletedComments], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
