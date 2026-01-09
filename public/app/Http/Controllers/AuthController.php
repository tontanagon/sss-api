<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(User::latest()->get());
    }

    public function genToken($user)
    {
        $tokenInfo = $user->createToken("cairocoders-ednalan");
        $token = $tokenInfo->plainTextToken;
        return $token;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required|string",
            "email" => "required|string|email|unique:users",
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

        $user->assignRole('Student');

        //Response
        return response()->json([
            "status" => true,
            "message" => "User registered successful"
        ],200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "email" => "required",
            "password" => "required",
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status' => false,
                'message' => $errorMessage,
            ];
            return response()->json($response, 400);
        }

        $user = User::with('roles')->where("email", $request->email)->first();

        //check user by password
        if (!empty($user)) {
            if (Hash::check($request->password, $user->password)) {
                $token = $this->genToken($user);

                return response()->json([
                    "status" => true,
                    "message" => "Login successful",
                    "user_info" => [
                        "id" => $user->id,
                        "name" => $user->name,
                        "user_code" => $user->user_code,
                        "user_grade" => $user->user_grade,
                        "email" => $user->email,
                        "id" => $user->id,
                        "token" => $token,
                        "role" => $user->getRoleNames(),
                    ]
                ],200);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "Invalid credentials"
            ],);
        }
    }

    public function loginWithMicrosoft(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "accessToken" => "required",
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status' => false,
                'message' => $errorMessage,
            ];
            return response()->json($response, 400);
        }

        $response = Http::withToken($request->accessToken)->get('https://graph.microsoft.com/v1.0/me');
        $data = $response->json();

        if (isset($data['error'])) {
            return response()->json([
                "status" => false,
                "message" => $data['error']['code'],
            ],404);
        }

        //user info from microsoft
        $user_name_microsoft = $data['displayName'];
        $email_microsoft = $data['userPrincipalName'];
        $provider_id = $data['id'];

        $user = User::updateOrCreate(
            ['email' => $email_microsoft],
            ['provider_id' => $provider_id, 'name' => $user_name_microsoft, 'login_type' => 'microsoft'],
        );

        if ($user->getRoleNames()->isEmpty()) {
            $user->assignRole('Student');
        }

        //create token
        $token = $this->genToken($user);

        return response()->json([
            "status" => true,
            "message" => "Login successful",
            "user_info" => [
                "id" => $user->id,
                "provider_id" => $user->provider_id,
                "name" => $user->name,
                "user_code" => $user->user_code,
                "user_grade" => $user->user_grade,
                "email" => $user->email,
                "token" => $token,
                "role" => $user->getRoleNames(),
            ]
        ],200);
    }

    public function loginWithGoogle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "accessToken" => "required",
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status' => false,
                'message' => $errorMessage,
            ];
            return response()->json($response, 400);
        }

        $response = Http::withToken($request->accessToken)->get('https://www.googleapis.com/oauth2/v1/userinfo?alt=json');
        $data = $response->json();

        if (isset($data['error'])) {
            return response()->json([
                "status" => false,
                "message" => $data['error']['message'],
            ],404);
        }

        //user info from google
        $user_name_google = $data['name'];
        $email_google = $data['email'];
        $provider_id = $data['id'];

        $user = User::updateOrCreate(
            ['email' => $email_google],
            ['provider_id' => $provider_id, 'name' => $user_name_google, 'login_type' => 'google'],
        );

        if ($user->getRoleNames()->isEmpty()) {
            $user->assignRole('Student');
        }

        //create token
        $token = $this->genToken($user);

        return response()->json([
            "status" => true,
            "message" => "Login successful",
            "user_info" => [
                "id" => $user->id,
                "provider_id" => $user->provider_id,
                "name" => $user->name,
                "user_code" => $user->user_code,
                "user_grade" => $user->user_grade,
                "email" => $user->email,
                "token" => $token,
                "role" => $user->getRoleNames(),
            ]
        ],200);
    }

    public function profile()
    {
        $userData = auth()->user();

        return response()->json([
            "status" => true,
            "message" => "Profile information",
            "data" => $userData
        ],200);
    }

    public function logout(Request $request)
    {
       auth()->user()->currentAccessToken()->delete();

        return response()->json([
            "status" => true,
            "message" => "User logged out",
        ],200);
    }
}
