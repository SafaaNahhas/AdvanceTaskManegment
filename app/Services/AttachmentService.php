<?php

namespace App\Services;

use Exception;
use App\Models\Task;
use App\Models\Attachment;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Encryption\DecryptException;

class AttachmentService
{
    /**
     * Handle file upload for a task.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Task $task
     * @return \App\Models\Attachment
     * @throws Exception
     */
    public function handleFileUpload($request, Task $task)
    {
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $this->validateFile($file);
            Log::info('File validation passed.');

            // Virus check
            if (!$this->scanFileWithVirusTotal($file)) {
                throw new Exception('Virus detected in the file.');
            }
            Log::info('Virus scan passed.');

            // Generate a safe file name and encrypt the content
            $filePath = $this->storeEncryptedFile($file);
            Log::info('File stored successfully.', ['filePath' => $filePath]);

            return $task->attachments()->create([
                'file_path' => $filePath,
                'file_type' => $file->getClientMimeType(),
                'user_id' => Auth::id(),
            ]);
        }

        throw new Exception('File not found in the request.');
    }

    /**
     * Validate the uploaded file.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @throws Exception
     */
    private function validateFile($file)
    {
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        // Check for path traversal
        $originalName = $file->getClientOriginalName();
        if ($this->hasPathTraversal($originalName)) {
            throw new Exception('Path traversal attempt detected in file name.');
        }

        // Check MIME type
        $mimeType = $file->getClientMimeType();
        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new Exception('File type not allowed.');
        }
    }

    /**
     * Check for path traversal in the file name.
     *
     * @param string $fileName
     * @return bool
     */
    private function hasPathTraversal($fileName)
    {
        return preg_match('/\.\.(\/|\\\)/', $fileName) || strpos($fileName, '/') !== false || strpos($fileName, '\\') !== false;
    }

    /**
 * Scan file with VirusTotal for viruses.
 *
 * @param \Illuminate\Http\UploadedFile $file
 * @return bool
 * @throws Exception
 */
private function scanFileWithVirusTotal($file)
{
    set_time_limit(180);
    // Get the API key
    $apiKey = config('services.virustotal.api_key');
    if (!$apiKey) {
        throw new Exception('VirusTotal API key not found.');
    }

    // Read file content
    $fileContent = file_get_contents($file->getRealPath());

    // Send the file to VirusTotal with timeout and retry settings
    $response = Http::timeout(120) // تحديد المهلة الزمنية بـ 120 ثانية
        ->retry(3, 5000) // إعادة المحاولة 3 مرات مع انتظار 5 ثواني بين المحاولات
        ->withHeaders(['x-apikey' => $apiKey])
        ->attach('file', $fileContent, $file->getClientOriginalName())
        ->post('https://www.virustotal.com/api/v3/files');

    if ($response->failed()) {
        throw new Exception('Failed to connect to VirusTotal.');
    }

    $fileId = $response->json()['data']['id'] ?? null;
    if (!$fileId) {
        throw new Exception('Failed to get file ID from VirusTotal.');
    }

    // Wait for analysis
    sleep(15);

    // Get analysis results with timeout and retry settings
    $analysisResponse = Http::timeout(120)
        ->retry(3, 5000)
        ->withHeaders(['x-apikey' => $apiKey])
        ->get("https://www.virustotal.com/api/v3/analyses/{$fileId}");

    if ($analysisResponse->failed()) {
        throw new Exception('Failed to get analysis results from VirusTotal.');
    }

    $maliciousCount = $analysisResponse->json()['data']['attributes']['stats']['malicious'] ?? 0;
    return $maliciousCount === 0;
}


    /**
     * Store the encrypted file content.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string
     * @throws Exception
     */
    private function storeEncryptedFile($file)
    {
        $fileName = Str::random(32);
        $extension = $file->getClientOriginalExtension();
        $safeFileName = "{$fileName}.{$extension}";
        $filePath = "file/{$safeFileName}";

        // Encrypt the content
        $fileContent = file_get_contents($file);
        $encryptedContent = Crypt::encrypt($fileContent);

        // Store the encrypted file
        Storage::disk('local')->put($filePath, $encryptedContent);
        return $filePath;
    }

    /**
     * Download the file and decrypt it.
     *
     * @param \App\Models\Attachment $attachment
     * @return \Illuminate\Http\Response
     */
    public function downloadFile(Attachment $attachment)
{
    try {
        $encryptedContent = Storage::disk('local')->get($attachment->file_path);

        // Log the size of the encrypted content for debugging
        Log::info("Encrypted content size: " . strlen($encryptedContent));

        $decryptedContent = Crypt::decrypt($encryptedContent);
        $mimeType = $attachment->file_type;

        return response($decryptedContent, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'attachment; filename="' . basename($attachment->file_path) . '"');
    } catch (DecryptException $e) {
        Log::error('Decryption error: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to decrypt file'], 500);
    } catch (Exception $e) {
        Log::error('Error while downloading file: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to download attachment'], 500);
    }
}

/**
 * Replace the file for a task (Update).
 *
 * @param \Illuminate\Http\Request $request
 * @param \App\Models\Task $task
 * @param \App\Models\Attachment $attachment
 * @return \App\Models\Attachment
 * @throws Exception
 */
public function updateFile($request, Task $task, Attachment $attachment)
{
    Storage::disk('local')->delete($attachment->file_path);

    if ($request->hasFile('attachment')) {
        $file = $request->file('attachment');

        $this->validateFile($file);
        Log::info('File validation passed for update.');

        if (!$this->scanFileWithVirusTotal($file)) {
            throw new Exception('Virus detected in the file.');
        }
        Log::info('Virus scan passed for update.');

        $filePath = $this->storeEncryptedFile($file);
        Log::info('File stored successfully for update.', ['filePath' => $filePath]);

        $attachment->update([
            'file_path' => $filePath,
            'file_type' => $file->getClientMimeType(),
            'user_id' => Auth::id(),
        ]);

        return $attachment;
    }

    throw new Exception('File not found in the request.');
}

    /**
     * Delete an attachment (Delete).
     *
     * @param \App\Models\Attachment $attachment
     * @return bool
     */
    public function deleteFile(Attachment $attachment)
    {
        try {
            // Delete the file from storage
            Storage::disk('local')->delete($attachment->file_path);

            // Delete the record from the database
            return $attachment->forceDelete();
        } catch (Exception $e) {
            Log::error('Error deleting attachment: ' . $e->getMessage());
            return false;
        }
    }

}
