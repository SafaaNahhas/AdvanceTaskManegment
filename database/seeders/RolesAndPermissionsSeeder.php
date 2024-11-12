<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $permissions = [
        'assign task',
        'update task',
        'update status tasks',
        'store task',
        'view task',
        'view tasks',
        'reassign task',
        'restore tasks',
        'destroy tasks',
        'forceDelete tasks',
        'trashedTasks',
        'daily tasks',
        'create comments',
        'upload attachments',
        'download attachments',
        'create reports',
        'create roles',
        'update roles',
        'delete roles',
        'view roles',
        'create permissions',
        'update permissions',
        'delete permissions',
        'view permissions',
    ];

    foreach ($permissions as $permission) {
        Permission::create(['name' => $permission]);
    }

    // $roleAdmin = Role::create(['name' => 'admin']);
    $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);

    $roleAdmin->givePermissionTo(Permission::all());

    $roleManager = Role::create(['name' => 'manager']);
    $roleManager->givePermissionTo([
        'assign task',
        'update task',
        'update status tasks',
        'store task',
        'view task',
        'restore tasks',
        'destroy tasks',
        'forceDelete tasks',
        'trashedTasks',
        'daily tasks',
        'download attachments',
        'view tasks',
        'reassign task',
        'create comments',
        'upload attachments',

    ]);

    $roleUser = Role::create(['name' => 'user']);
    $roleUser->givePermissionTo(['view task']);

    $Admin = User::firstOrCreate([
        'name' => 'Safaa',
        'email' => 'Safaa@gmail.com',
        'password' => Hash::make('12345678'),
    ]);
    $Admin->assignRole('admin');

    $Manager = User::firstOrCreate([
        'name' => 'Manager',
        'email' => 'Manager@gmail.com',
        'password' => Hash::make('12345678'),
    ]);
    $Manager->assignRole('manager');

    $User = User::firstOrCreate([
        'name' => 'User',
        'email' => 'User@gmail.com',
        'password' => Hash::make('12345678'),
    ]);
    $User->assignRole('user');

}
}
