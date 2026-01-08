<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function storageImage($image)
    {
        $date = Carbon::now()->format('Ymd_His');
        $extension = $image->getClientOriginalExtension();
        $filename = "category_{$date}." . $extension;

        return Storage::disk('images')->putFileAs('', $image, $filename);
    }

    public function getAllUser(Request $request)
    {
        $search_text = $request->search_text ?? '';
        $page = $request->page ?? 1;
        $paginate = $request->limit ?? 10;

        $users = User::with('roles')
                    ->where(function ($q) use ($search_text) {
                        $q->where('name', 'like', "%{$search_text}%")
                            ->orWhere('user_code', 'like', "%{$search_text}%")
                            ->orWhere('email', 'like', "%{$search_text}%");
                    })
                    ->paginate($paginate, ['*'], 'page', $page);

        if ($users->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No users found.'
            ], 404);
        }

        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'login_type' => $user->login_type,
                'user_code' => $user->user_code,
                'user_grade' => $user->user_grade,
                'email' => $user->email,
                'roles' => $user->getRoleNames()
            ];
        });

        $usersArray = $users->toArray();
        $usersArray['data'] = $data;

        return response()->json($usersArray, 200);
    }

    public function getUser()
    {
        $users = User::with('roles')->get();

        if ($users->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No users found.'
            ], 404);
        }

        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'login_type' => $user->login_type,
                'user_code' => $user->user_code,
                'user_grade' => $user->user_grade,
                'email' => $user->email,
                'roles' => $user->getRoleNames()
            ];
        });

        return response()->json($data, 200);
    }

    public function getUserById($id)
    {
        $data = [];
        $user = User::with('roles')->find($id);

        if (!$user) {
            $response = [
                'status' => false,
                'message' => 'User not found.',
            ];
            return response()->json($response, 404);
        }
        $data['id'] = $user->id;
        $data['name'] = $user->name;
        $data['user_code'] = $user->user_code;
        $data['user_grade'] = $user->user_grade;
        $data['login_type'] = $user->login_type;
        $data['email'] = $user->email;
        $data['roles'] = $user->getRoleNames();

        return response()->json($data, 200);
    }

    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required|string",
            "email" => "required|string|email|unique:users",
            "roles" => "required",
            // "login_type" => "required|in:application,microsoft,cmu",
            "password" => "required|string",
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status' => false,
                'message' => $errorMessage,
            ];
            return response()->json($response, 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'login_type' => 'application',
            'password' => bcrypt($request->password),
        ]);

        $rolesArray = explode(',', $request->roles);
        $user->assignRole($rolesArray);

        //Response
        return response()->json([
            "status" => true,
            "message" => "User create successful"
        ], 200);
    }

    public function updateUser(Request $request)
    {
        $user = User::find($request->id);
        // $filepath = '';

        if (!$user) {
            return response()->json([
                "status" => false,
                "message" => "User not found"
            ], 404);
        }

        // if ($request->file('image')) {
        //     if (isset($user->image)) {
        //         Storage::disk('images')->delete($user->image);
        //     }
        //     $filepath = $this->storageImage($request->file('image'));
        // } else {
        //     if ($request->image_old_path != $user->image) {
        //         Storage::disk('images')->delete($user->image);
        //     }
        //     $filepath = $request->image_old_path;
        // }

        if ($user->login_type === 'cmu') {
            $user->user_grade = $request->user_grade;
        } else {
            $user->user_code = $request->user_code;
            $user->user_grade = $request->user_grade;
            $user->name = $request->name;
            $user->email = $request->email;
            if (isset($request->password)) {
                $user->password = bcrypt($request->password);
            }
        }

        $user->save();
        $rolesArray = explode(',', $request->roles);
        $user->syncRoles($rolesArray);

        return response()->json([
            "status" => true,
            "message" => "Update user successful"
        ]);
    }

    public function deleteUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                "status" => false,
                "message" => "User not found"
            ], 404);
        }

        $user->delete();

        return response()->json([
            "status" => true,
            "message" => "Delete user successful"
        ]);
    }

    public function getTeacher()
    {
        $teachers = User::role('Teacher')->get()->pluck('name');
        if ($teachers->isEmpty()) {
            return response()->json([
                'status' => false,
                'data' => [
                    'value' => 'ไม่มีอาจารในขณะนี้',
                    'label' => 'ไม่มีอาจารในขณะนี้'
                ]
            ], 404);
        }
        $data = [];
        foreach ($teachers as $teacher) {
            $data[] = [
                'value' => $teacher,
                'label' => $teacher
            ];
        }

        return response()->json(['data' => $data], 200);
    }
}
