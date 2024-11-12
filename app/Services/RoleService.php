<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoleService
{
    /**
     * Create a new role with optional permissions.
     *
     * @param string $roleName
     * @param array|null $permissions
     * @return Role
     * @throws \Exception
     */
    public function createRole(string $roleName, ?array $permissions = null): Role
    {
        try {
            $role = Role::create(['name' => $roleName]);

            if ($permissions) {
                $role->syncPermissions($permissions);
            }

            return $role;
        } catch (Exception $e) {
            Log::error('Failed to create role: ' . $e->getMessage());
            throw new Exception('Failed to create role.');
        }
    }

    /**
     * Update an existing role and its permissions.
     *
     * @param int $roleId
     * @param string $roleName
     * @param array|null $permissions
     * @return Role
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function updateRole(int $roleId, string $roleName, ?array $permissions = null): Role
    {
        try {
            $role = Role::findOrFail($roleId);
            $role->name = $roleName;
            $role->save();

            if ($permissions) {
                $role->syncPermissions($permissions);
            }

            return $role;
        } catch (ModelNotFoundException $e) {
            Log::error('Role not found: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Failed to update role: ' . $e->getMessage());
            throw new Exception('Failed to update role.');
        }
    }

    /**
     * Get all roles with their permissions (only permission names).
     *
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function getAllRoles()
    {

        try {
            return Role::with('permissions:name')->get()->map(function ($role) {
                $role->permissions = $role->permissions->pluck('name');
                return $role;
            });
        } catch (Exception $e) {
            Log::error('Failed to retrieve roles: ' . $e->getMessage());
            throw new Exception('Failed to retrieve roles.');
        }
    }




    /**
     * Assign permissions to a role.
     *
     * @param int $roleId
     * @param array $permissions
     * @return Role
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function assignPermissionsToRole(int $roleId, array $permissions): Role
    {
        try {
            $role = Role::findOrFail($roleId);
            $role->syncPermissions($permissions);
            return $role;
        } catch (ModelNotFoundException $e) {
            Log::error('Role not found: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Failed to assign permissions: ' . $e->getMessage());
            throw new Exception('Failed to assign permissions to role.');
        }
    }
    /**
     * Remove specific permissions from a role.
     *
     * @param int $roleId
     * @param array $permissions
     * @return Role
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function removePermissionsFromRole(int $roleId, ?array $permissions = null): bool
{
    try {
        $role = Role::findOrFail($roleId);

        // التحقق مما إذا كان $permissions ليس null
        if ($permissions) {
            $role->revokePermissionTo($permissions);
        }

        return true;
    } catch (ModelNotFoundException $e) {
        Log::error('Role not found: ' . $e->getMessage());
        throw $e;
    } catch (Exception $e) {
        Log::error('Failed to remove permissions from role: ' . $e->getMessage());
        throw new Exception('Failed to remove permissions from role.');
    }
}


    /**
     * Delete a role and detach all its users and permissions.
     *
     * @param int $roleId
     * @return bool
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function deleteRole(int $roleId): bool
    {
        try {
            $role = Role::findOrFail($roleId);
            $role->permissions()->detach();
            $role->users()->detach();
            return $role->delete();
        } catch (ModelNotFoundException $e) {
            Log::error('Role not found: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Failed to delete role: ' . $e->getMessage());
            throw new Exception('Failed to delete role.');
        }
    }
}
