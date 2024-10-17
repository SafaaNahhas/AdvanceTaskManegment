<?php

namespace App\Http\Controllers\RoleAndPermission;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserRoleController extends Controller
{
    public function assignRole(Request $request, $userId)
    {
        $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::findOrFail($userId);
        $user->assignRole($request->role);

        return response()->json(['message' => 'تم تعيين الدور بنجاح', 'user' => $user], 200);
    }

    public function removeRole(Request $request, $userId)
    {
        $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::findOrFail($userId);
        $user->removeRole($request->role);

        return response()->json(['message' => 'تم إزالة الدور بنجاح', 'user' => $user], 200);
    }

    public function getUserRoles($userId)
    {
        $user = User::findOrFail($userId);
        $roles = $user->roles;

        return response()->json(['roles' => $roles], 200);
    }
}
