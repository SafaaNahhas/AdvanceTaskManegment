<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Session\Middleware\StartSession;
use App\Http\Controllers\TaskManagment\TaskController;
use App\Http\Controllers\TaskManagment\ReportController;
use App\Http\Controllers\TaskManagment\CommentController;
use App\Http\Controllers\RoleAndPermission\RoleController;
use App\Http\Controllers\TaskManagment\AttachmentController;
use App\Http\Controllers\RoleAndPermission\UserRoleController;
use App\Http\Controllers\RoleAndPermission\PermissionController;
use App\Http\Controllers\TaskManagment\TaskStatusUpdateController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/




Route::middleware(  StartSession::class)->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
});


Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('roles/{roleId}/permissions', [RoleController::class, 'assignPermissions']); // Assign permissions to role
    Route::delete('roles/{roleId}/permissions/{permissionId}', [RoleController::class, 'removePermission']); // Remove permission from role
    Route::post('/permissions', [PermissionController::class, 'store']);
    Route::put('/permissions/{id}', [PermissionController::class, 'update']);
    Route::delete('/permissions/{id}', [PermissionController::class, 'destroy']);
    Route::get('/permissions', [PermissionController::class, 'index']);

    Route::post('/users/{id}/roles', [UserRoleController::class, 'assignRole']);
    Route::delete('/users/{id}/roles', [UserRoleController::class, 'removeRole']);
    Route::get('/users/{id}/roles', [UserRoleController::class, 'getUserRoles']);
});





Route::middleware(['auth:api','throttle:api',StartSession::class])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::get('tasks/trashed', [TaskController::class, 'trashedTasks'])->middleware('permission:trashedTasks');
    Route::get('/tasks/blocked', [TaskController::class, 'blockedTasks'])->middleware('permission:view tasks');

    Route::post('/tasks', [TaskController::class, 'store'])->middleware('permission:store task');
    Route::put('/tasks/{id}/reassign', [TaskController::class, 'reassign'])->middleware('permission:reassign task');
    Route::post('/tasks/{id}/assign', [TaskController::class, 'assign'])->middleware('permission:assign task');
    Route::get('/tasks/{id}', [TaskController::class, 'show'])->middleware('permission:view task');
    Route::get('/tasks', [TaskController::class, 'index'])->middleware('permission:view tasks');

    Route::post('/tasks/{id}/comments', [CommentController::class, 'store'])->middleware('permission:create comments');
    Route::get('/tasks/{taskId}/comments', [CommentController::class, 'index']);
    Route::put('/tasks/{taskId}/comments/{commentId}', [CommentController::class, 'update']);
    Route::delete('/tasks/{taskId}/comments/{commentId}', [CommentController::class, 'destroy']);
    Route::delete('/tasks/{taskId}/comments/{commentId}/permanentDelete', [CommentController::class, 'permanentDelete']);
    Route::get('/tasks/{taskId}/comments/deleted', [CommentController::class, 'showDeleted']);
    Route::post('/tasks/{taskId}/comments/{commentId}/restore', [CommentController::class, 'restore']);

    Route::post('/tasks/{id}/attachments', [AttachmentController::class, 'store'])->middleware('permission:upload attachments');
    Route::get('/attachments/download/{attachmentId}', [AttachmentController::class, 'download'])->middleware('permission:download attachments');

    Route::post('/tasks/{task}/attachments/{attachment}/update', [AttachmentController::class, 'update']);
    Route::delete('/attachments/{attachment}/delete', [AttachmentController::class, 'softDelete']);
    Route::delete('/attachments/{id}/force-delete', [AttachmentController::class, 'forceDelete']);
    Route::post('/attachments/{id}/restore', [AttachmentController::class, 'restore']);
    Route::get('/attachments/trashed', [AttachmentController::class, 'trashedFiles']);

    Route::get('/reports', [ReportController::class, 'generateDailyTasksReport'])->middleware('permission:create reports');

    Route::get('/reports/daily-tasks', [TaskController::class, 'dailyTasksReport'])->middleware('permission:daily tasks');

    Route::put('/tasks/{id}/status', [TaskStatusUpdateController::class, 'updateStatus'])->middleware('permission:update status tasks');

     Route::post('tasks/restore/{id}', [TaskController::class, 'restore'])->middleware('permission:restore tasks');

     Route::delete('tasks/{id}', [TaskController::class, 'destroy'])->middleware('permission:destroy tasks');

     Route::delete('tasks/force-delete/{id}', [TaskController::class, 'forceDelete'])->middleware('permission:forceDelete tasks');
     Route::put('/tasks/{id}', [TaskController::class, 'update'])->middleware('permission:update task');


     Route::get('/reports/completed-tasks', [TaskController::class, 'getCompletedTasks']);
     Route::get('/reports/overdue-tasks', [TaskController::class, 'getOverdueTasks']);
     Route::get('/reports/tasks-by-user/{userId}', [TaskController::class, 'getTasksByUser']);
});
