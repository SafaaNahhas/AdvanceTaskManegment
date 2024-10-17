<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateDailyTasksReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $user;
    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
            $today = now()->startOfDay();
            $tasks = Task::whereHas('statusUpdates', function ($query) use ($today) {
                $query->where('created_at', '>=', $today);
            })->with(['statusUpdates', 'dependencies', 'dependents'])->get();

            $csvData = [];
            $csvData[] = ['Task ID', 'Title', 'Old Status', 'New Status', 'Updated By', 'Updated At'];

            foreach ($tasks as $task) {
                foreach ($task->statusUpdates as $update) {
                    $csvData[] = [
                        $task->id,
                        $task->title,
                        $update->old_status,
                        $update->new_status,
                        $update->user->name,
                        $update->created_at->toDateTimeString(),
                    ];
                }
            }

            $filename = 'daily_tasks_report_' . now()->format('Y_m_d') . '.csv';
            $filePath = 'reports/' . $filename;

            $handle = fopen(storage_path('app/' . $filePath), 'w');

            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);}    }

