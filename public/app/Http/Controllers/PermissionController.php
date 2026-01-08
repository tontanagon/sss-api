<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function getPermissions()
{
    // 1. เช็คว่าเข้ามาถึง Controller หรือยัง
    \Illuminate\Support\Facades\Log::info('Start getPermissions'); 

    $permissions = Permission::get();

    // 2. เช็คว่าดึงข้อมูลสำเร็จไหม
    \Illuminate\Support\Facades\Log::info('Got permissions count: ' . $permissions->count());

    if ($permissions->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'Permissions not found.'
        ], 404);
    }

    return response()->json($permissions, 200);
}

}
