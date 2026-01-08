<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Type;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class TypeController extends Controller
{
    // public function storageImage($image)
    // {
    //     $filepath = Storage::disk('images')->put('', $image);
    //     return $filepath;
    // }

    public function getType()
    {
        $pagination = $request->limit ?? 10;
        $search_text = $request->search_text ?? '';

        $type = Type::where('name', 'like', "%{$search_text}%")->paginate($pagination);

        return response()->json($type, 200);
    }

    public function getTypeById($id)
    {
        $type = Type::find($id);

        return response()->json($type);
    }


    public function createType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required|string",
            "status" => "required|string|in:active,inactive",
            "descritpion" => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status' => false,
                'message' => $errorMessage,
            ];
            return response()->json($response, 400);
        }

        Type::create([
            'name' => $request->name,
            'status' => $request->status,
            'description' => $request->description,
        ]);

        return response()->json([
            "status" => true,
            "message" => "Create Type successful"
        ]);
    }

    public function updateType(Request $request)
    {
        $type = Type::find($request->id);
        if (!$type) {
            return response()->json([
                "status" => false,
                "message" => "Type not found"
            ], 404);
        }

        $type->name = $request->name;
        $type->status = $request->status;
        $type->description = $request->description;
        $type->save();

        return response()->json([
            "status" => true,
            "message" => "Update Type successful"
        ]);
    }

    public function deleteType(Request $request)
    {
        $type = Type::find($request->id);

        if (!$type) {
            return response()->json([
                "status" => false,
                "message" => "Type not found"
            ], 404);
        }

        $has_products = $type->products()->count();

        if ($has_products) {
            return response()->json([
                'status' => false,
                'message' => 'This type has products, please remove product first.'
            ], 409);
        }

        $type->delete();
        return response()->json([
            "status" => true,
            "message" => "Delete Type successful"
        ]);
    }
}
