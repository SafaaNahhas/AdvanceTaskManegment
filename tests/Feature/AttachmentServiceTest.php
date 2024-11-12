<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use App\Services\AttachmentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AttachmentServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $attachmentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attachmentService = app(AttachmentService::class);
    }
    /**
     * Test file validation.
     */
    public function test_invalid_file_type()
    {
        $this->expectException(\Exception::class);

        $user = User::factory()->create();
        $task = Task::factory()->create();
        $file = UploadedFile::fake()->create('document.txt', 100);

        // Simulate file upload with invalid file type
        $this->attachmentService->handleFileUpload(request()->merge(['attachment' => $file]), $task);
    }

    /**
     * Test scanning file with VirusTotal.
     */
    public function test_file_scan_with_virustotal_failure()
    {
        $this->expectException(\Exception::class);

        $user = User::factory()->create();
        $task = Task::factory()->create();
        $file = UploadedFile::fake()->image('infected_file.jpg');

        // Simulate virus detection failure
        $this->attachmentService->handleFileUpload(request()->merge(['attachment' => $file]), $task);
    }


}
