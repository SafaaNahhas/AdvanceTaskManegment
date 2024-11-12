<?php

namespace App\Http\Controllers\RoleAndPermission;



use App\Services\RoleService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest\StoreRoleRequest;
use App\Http\Requests\RoleRequest\UpdateRoleRequest;

class RoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Store a new role with permissions.
     *
     * @param StoreRoleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreRoleRequest $request)
    {

            $role = $this->roleService->createRole($request->name, $request->permissions);
            return response()->json(['message' => 'Role created successfully', 'role' => $role], 201);

    }
    /**
     * Display a listing of roles with permissions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $roles = $this->roleService->getAllRoles();
        return response()->json(['roles' => $roles], 200);
    }
    /**
     * Assign permissions to a role.
     *
     * @param int $roleId
     * @param array $permissions
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignPermission($roleId, Request $request)
    {
        $permissions = $request->input('permissions');
        $role = $this->roleService->assignPermissionsToRole($roleId, $permissions);
        return response()->json(['message' => 'Permissions assigned successfully', 'role' => $role], 200);
    }
    /**
     * Remove specific permissions from a role.
     *
     * @param int $roleId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removePermission($roleId, Request $request)
    {
        $permissions = $request->input('permissions');
        $role = $this->roleService->removePermissionsFromRole($roleId, $permissions);
        return response()->json(['message' => 'Permissions removed successfully', 'role' => $role], 200);
    }

    /**
     * Update an existing role and its permissions.
     *
     * @param UpdateRoleRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRoleRequest $request, $id)
    {

            $role = $this->roleService->updateRole($id, $request->name, $request->permissions);
            return response()->json(['message' => 'Role updated successfully', 'role' => $role], 200);

    }

    /**
     * Delete a role.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {

            $this->roleService->deleteRole($id);
            return response()->json(['message' => 'Role deleted successfully'], 200);

    }
}
