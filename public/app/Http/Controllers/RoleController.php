<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    // public function storageImage($image)
    // {
    //     $date = Carbon::now()->format('Ymd_His');
    //     $extension = $image->getClientOriginalExtension();
    //     $filename = "category_{$date}." . $extension;

    //     return Storage::disk('images')->putFileAs('', $image, $filename);
    // }

    public function getRole()
    {
        $role_permissions = Role::with('permissions')->get();
        if ($role_permissions->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No Roles found.'
            ], 404);
        }

        $data = [];

        foreach ($role_permissions as $role) {
            $roleData = [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => [],
            ];
            foreach ($role->permissions as $permission) {
                $roleData['permissions'][] = [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ];
            }
            $data[] = $roleData;
        }

        return response()->json($data, 200);
    }

    public function getRoleById($id)
    {
        $role = Role::with('permissions')->find($id);
        $permissions = $role->permissions()->get();
        if (!$role) {
            $response = [
                'status' => false,
                'message' => 'Role not found.',
            ];
            return response()->json($response, 404);
        }

        $data['id'] = $role->id;
        $data['name'] = $role->name;
        $data['permissions'] = [];
        foreach ($permissions as $permission) {
            $data['permissions'][] = $permission->name;
        }

        return response()->json($data, 201);
    }

    public function createRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required|string|unique:roles",
            "permissions" => "required",
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status' => false,
                'message' => $errorMessage,
            ];
            return response()->json($response, 400);
        }

        $permissionArray = explode(',', $request->permissions);
        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

        $role->givePermissionTo($permissionArray);

        return response()->json([
            "status" => true,
            "message" => "Role create successful"
        ], 200);
    }

    public function updateRole(Request $request)
    {
        $role = Role::find($request->id);

        $roleoverlap = Role::where('name', $request->name)->get();

        if (!$role) {
            return response()->json([
                "status" => false,
                "message" => "Role not found"
            ], 404);
        }

        if (!empty($roleoverlap) && $role->name != $request->name) {
            return response()->json([
                "status" => false,
                "message" => "The name has already been taken"
            ], 400);
        }

        $role->name = $request->name;
        $role->save();

        $permissionArray = explode(',', $request->permissions);
        $role->syncPermissions($permissionArray);

        return response()->json([
            "status" => true,
            "message" => "Update role successful"
        ]);
    }

    public function deleteRole($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                "status" => false,
                "message" => "Role not found"
            ], 404);
        }

        $has_user = $role->users->count();
        if ($has_user) {
            return response()->json([
                'status' => false,
                'message' => "This role has user, please remove or change user's role first."
            ], 409);
        }

        $role->delete();

        return response()->json([
            "status" => true,
            "message" => "Delete role successful"
        ]);
    }
}
