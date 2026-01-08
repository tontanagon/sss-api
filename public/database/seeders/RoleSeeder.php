<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleAdministrator = Role::create(['name' => 'Administrator']);
        $roleStaff = Role::create(['name' => 'Staff']);
        $roleTeacher = Role::create(['name' => 'Teacher']);
        $roleStudent = Role::create(['name' => 'Student']);

        $permissionUse = Permission::create(['name' => 'ใช้งานระบบยืม-คืน']);
        $permissionManageProduct = Permission::create(['name' => 'จัดการข้อมูลวัสดุ']);
        $permissionManageUser = Permission::create(['name' => 'จัดการข้อมูลผู้ใช้']);
        $permissionManageApprove = Permission::create(['name' => 'จัดการรายการอนุมัติ']);
        $permissionManageRequest = Permission::create(['name' => 'จัดการรายการคำขอ']);
        $permissionManageWebSetting = Permission::create(['name' => 'จัดการตั้งค่าเว็บไซต์']);

        $roleAdministrator->givePermissionTo([
            $permissionUse,
            $permissionManageProduct,
            $permissionManageUser,
            $permissionManageRequest,
            $permissionManageWebSetting,
        ]);

        $roleStaff->givePermissionTo([
            $permissionUse,
            $permissionManageProduct,
        ]);

        $roleTeacher->givePermissionTo([
            $permissionUse,
            $permissionManageApprove,
        ]);

        $roleStudent->givePermissionTo([
            $permissionUse,
        ]);

        $user = User::create([
            'name' => 'admin',
            'email' => 'sss@admin.com',
            'login_type' => 'application',
            'password' => 'sss-mininggarden2568'
        ]);
        $user->assignRole('Administrator');
    }
}
