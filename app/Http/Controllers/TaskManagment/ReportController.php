<?php

namespace App\Http\Controllers\TaskManagment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Jobs\GenerateDailyTasksReport;

class ReportController extends Controller
{
    public function generateDailyTasksReport(Request $request)
    {

        GenerateDailyTasksReport::dispatch(Auth::user());

        return response()->json(['message' => 'تم بدء توليد تقرير المهام اليومية في الخلفية. سيتم إشعارك عند الانتهاء.'], 202);
    }
    
}
