<?php

namespace App\Http\Controllers\TaskManagment;

use Exception;
use App\Models\Task;
use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Http\Requests\AttachmentRequest\StoreAttachmentRequest;


class AttachmentController extends Controller
{
    protected $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * Store a new attachment for a given task.
     *
     * @param StoreAttachmentRequest $request
     * @param int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreAttachmentRequest $request, $taskId)
    {
        try {
            // Validate request
            $validator = Validator::make($request->validated(), [
                'attachment' => 'required|file|max:2048|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx', // Allowed file types
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Find the task
            $task = Task::find($taskId);
            if (!$task) {
                return response()->json(['error' => 'Task not found'], 404);
            }

            // Handle file upload via service
            $attachment = $this->attachmentService->handleFileUpload($request, $task);
            return response()->json($attachment, 201);

        } catch (Exception $e) {
            Log::error('Error storing attachment: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to store attachment'], 500);
        }
    }

    /**
     * Download an attachment and decrypt it.
     *
     * @param int $attachmentId
     * @return \Illuminate\Http\Response
     */

    public function download($attachmentId)
        {
            $attachment = Attachment::find($attachmentId);

            if (!$attachment) {
                return response()->json(['error' => 'المرفق غير موجود'], 404);
            }

            $encryptedContent = Storage::disk('local')->get($attachment->file_path);

            try {
                $decryptedContent = Crypt::decrypt($encryptedContent);
            } catch (DecryptException $e) {
                return response()->json(['error' => 'فشل في فك تشفير الملف'], 500);
            }

            $mimeType = $attachment->file_type;

            return response($decryptedContent, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'attachment; filename="' . basename($attachment->file_path) . '"');
        }
         /**
     * Update an attachment file.
     *
     * @param StoreAttachmentRequest $request
     * @param \App\Models\Task $task
     * @param \App\Models\Attachment $attachment
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(StoreAttachmentRequest $request, Task $task, Attachment $attachment)
    {
        try {
            $this->attachmentService->updateFile($request, $task, $attachment);
            return response()->json(['message' => 'File updated successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Soft delete an attachment file.
     *
     * @param \App\Models\Attachment $attachment
     * @return \Illuminate\Http\JsonResponse
     */
    public function softDelete(Attachment $attachment)
    {
        try {
            $attachment->delete(); // Soft delete
            return response()->json(['message' => 'File deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Permanently delete a file (hard delete).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDelete($id)
    {
        try {
            $attachment = Attachment::withTrashed()->findOrFail($id);
            $this->attachmentService->deleteFile($attachment); // Permanently delete
            return response()->json(['message' => 'File permanently deleted'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Restore a soft deleted file.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($id)
    {
        try {
            $attachment = Attachment::withTrashed()->findOrFail($id);
            $attachment->restore(); // Restore the soft-deleted attachment
            return response()->json(['message' => 'File restored successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display a list of soft deleted files.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function trashedFiles()
    {
        try {
            $trashedFiles = Attachment::onlyTrashed()->get();
            return response()->json($trashedFiles, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
