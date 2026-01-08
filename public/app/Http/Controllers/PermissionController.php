<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function getPermissions()
    {
        $permissions = Permission::get();
        if ($permissions->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Permissions not found.'
            ], 404);
        }

        return response()->json($permissions, 200);
    }

}
