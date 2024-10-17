<?php

namespace App\Http\Controllers\RoleAndPermission;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use App\Http\Requests\RoleRequest\StoreRoleRequest;
use App\Http\Requests\RoleRequest\UpdateRoleRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoleController extends Controller
{
public function store(StoreRoleRequest $request)
{

    $role = Role::create(['name' => $request->name]);

    if ($request->has('permissions')) {
        $role->syncPermissions($request->permissions);
    }

    return response()->json(['message' => 'تم إنشاء الدور بنجاح', 'role' => $role], 201);
}

public function update(UpdateRoleRequest $request, $id)
{
    $role = Role::findOrFail($id);

    $role->name = $request->name;
    $role->save();

    if ($request->has('permissions')) {
        $role->syncPermissions($request->permissions);
    }

    return response()->json(['message' => 'تم تحديث الدور بنجاح', 'role' => $role], 200);
}

public function destroy($id)
{
    $role = Role::findOrFail($id);
    $role->permissions()->detach();
    $role->users()->detach();
    $role->delete();

    return response()->json(['message' => 'تم حذف الدور بنجاح'], 200);
}

public function index()
{
    $roles = Role::with('permissions')->get();
    return response()->json(['roles' => $roles], 200);
}
  /**
     * Assign permissions to a role.
     *
     * @param int $roleId
     * @param array $permissions
     * @return string
     * @throws \Exception
     */
    public function assignPermissions(Request $request, int $roleId)
    {
        $permissions = $request->input('permissions', []); // تأكد من أن permissions مصفوفة

        try {
            $role = Role::findOrFail($roleId);
            // Assign permissions without detaching existing ones
            $role->syncPermissions($permissions);

            return response()->json(['message' => 'Permissions assigned successfully'], 200);
        } catch (ModelNotFoundException $e) {
            Log::error('Role not found: ' . $e->getMessage());
            return response()->json(['error' => 'Role not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error assigning permissions: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to assign permissions'], 500);
        }
    }


    /**
     * Remove a permission from a role.
     *
     * @param int $roleId
     * @param int $permissionId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function removePermission(int $roleId, int $permissionId)
    {
        try {
            $role = Role::findOrFail($roleId);
            $permission = Permission::findOrFail($permissionId);
            // Detach the permission from the role
            $role->revokePermissionTo($permission);

            return response()->json(['message' => 'Permission removed successfully'], 200);
        } catch (ModelNotFoundException $e) {
            Log::error('Role or Permission not found: ' . $e->getMessage());
            return response()->json(['error' => 'Role or Permission not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error removing permission: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to remove permission'], 500);
        }
    }
}
