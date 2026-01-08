<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSettingWebsiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionManageWebSetting = Permission::create([
            'name' => 'จัดการตั้งค่าเว็บไซต์'
        ]);

        $roleAdministrator = Role::findByName('Administrator');

        $roleAdministrator->givePermissionTo($permissionManageWebSetting);
    }
}
