<?php

namespace App\Http\Controllers\RoleAndPermission;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use App\Http\Requests\PermissionRequest\PermissionRequest;

class PermissionController extends Controller
{
     public function store(PermissionRequest $request)
     {


         $permission = Permission::create(['name' => $request->name]);

         return response()->json(['message' => 'تم إنشاء الصلاحية بنجاح', 'permission' => $permission], 201);
     }

     public function update(PermissionRequest $request, $id)
     {
         $permission = Permission::findOrFail($id);



         $permission->name = $request->name;
         $permission->save();

         return response()->json(['message' => 'تم تحديث الصلاحية بنجاح', 'permission' => $permission], 200);
     }

     public function destroy($id)
     {
         $permission = Permission::findOrFail($id);
         $permission->delete();

         return response()->json(['message' => 'تم حذف الصلاحية بنجاح'], 200);
     }

     public function index()
     {
         $permissions = Permission::all();
         return response()->json(['permissions' => $permissions], 200);
     }
}
