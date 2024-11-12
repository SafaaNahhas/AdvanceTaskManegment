<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Exception;
use Illuminate\Support\Facades\Log;

class PermissionService
{
    /**
     * Create a new permission.
     *
     * @param string $name
     * @return \Spatie\Permission\Models\Permission
     * @throws \Exception
     */
    public function createPermission(string $name)
    {
        try {
            $permission = Permission::create(['name' => $name]);

            // تأكد من أن الـ permission قد تم حفظه بنجاح
            if (!$permission) {
                throw new Exception('Failed to save permission to the database.');
            }

            return $permission;
        } catch (Exception $e) {
            Log::error('Error creating permission: ' . $e->getMessage());
            throw new Exception('Failed to create permission');
        }
    }


    /**
     * Update an existing permission.
     *
     * @param int $id
     * @param string $name
     * @return \Spatie\Permission\Models\Permission
     * @throws \Exception
     */
    public function updatePermission(int $id, string $name)
    {
        try {
            $permission = Permission::findOrFail($id);
            $permission->name = $name;
            $permission->save();
            return $permission;
        } catch (Exception $e) {
            Log::error('Error updating permission with ID ' . $id . ': ' . $e->getMessage());
            throw new Exception('Failed to update permission');
        }
    }

    /**
     * Delete a permission.
     *
     * @param int $id
     * @throws \Exception
     */
    public function deletePermission(int $id)
    {
        try {
            $permission = Permission::findOrFail($id);
            $permission->delete();
        } catch (Exception $e) {
            Log::error('Error deleting permission with ID ' . $id . ': ' . $e->getMessage());
            throw new Exception('Failed to delete permission');
        }
    }

    /**
     * Get all permissions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \Exception
     */
    public function getAllPermissions()
    {
        try {
            return Permission::all();
        } catch (Exception $e) {
            Log::error('Error fetching permissions: ' . $e->getMessage());
            throw new Exception('Failed to fetch permissions');
        }
    }
}
