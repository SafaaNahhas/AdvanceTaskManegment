<?php

namespace App\Http\Controllers\RoleAndPermission;

use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use App\Http\Requests\PermissionRequest\PermissionRequest;
use Exception;

class PermissionController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Store a new permission.
     *
     * @param \App\Http\Requests\PermissionRequest\PermissionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(PermissionRequest $request)
    {
        try {
            $permission = $this->permissionService->createPermission($request->name);
            return response()->json(['message' => 'تم إنشاء الصلاحية بنجاح', 'permission' => $permission], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing permission.
     *
     * @param \App\Http\Requests\PermissionRequest\PermissionRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(PermissionRequest $request, $id)
    {
        try {
            $permission = $this->permissionService->updatePermission($id, $request->name);
            return response()->json(['message' => 'تم تحديث الصلاحية بنجاح', 'permission' => $permission], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a permission.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $this->permissionService->deletePermission($id);
            return response()->json(['message' => 'تم حذف الصلاحية بنجاح'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all permissions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $permissions = $this->permissionService->getAllPermissions();
            return response()->json(['permissions' => $permissions], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
