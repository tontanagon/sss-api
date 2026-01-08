<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function storageImage($image)
    {
        $date = Carbon::now()->format('Ymd_His');
        $extension = $image->getClientOriginalExtension();
        $filename = "category_{$date}." . $extension;

        return Storage::disk('images')->putFileAs('category', $image, $filename);
    }

    public function getCategory(Request $request)
    {
        $pagination = $request->limit ?? 10;
        $search_text = $request->search_text ?? '';

        $cate = Category::where('name', 'like', "%{$search_text}%")->paginate($pagination);

        return response()->json($cate, 200);
    }

    public function getCategoryById($id)
    {
        $cate = Category::find($id);

        return response()->json($cate);
    }

    public function createCategory(Request $request)
    {
        $filepath = '';
        $validator = Validator::make($request->all(), [
            "name" => "required|string",
            "status" => "required|string|in:active,inactive",
            "descritpion" => 'nullable|string',
            "image" => 'nullable|file|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status' => false,
                'message' => $errorMessage,
            ];
            return response()->json($response, 400);
        }

        if ($request->file('image')) {
            $filepath = '/storage/images/' . $this->storageImage($request->image);
        }

        Category::create([
            'name' => $request->name,
            'image' =>  $filepath,
            'status' => $request->status,
            'description' => $request->description,
        ]);

        return response()->json([
            "status" => true,
            "message" => "Create Category successful"
        ]);
    }

    public function updateCategory(Request $request)
    {
        $cate = Category::find($request->id);
        $filepath = '';

        if (!$cate) {
            return response()->json([
                "status" => false,
                "message" => "Category not found"
            ], 404);
        }

        if ($request->file('image')) {
            if (isset($cate->image)) {
                $relativePath = str_replace('/storage/images/', '', $cate->image);
                Storage::disk('images')->delete($relativePath);
            }
            $filepath = '/storage/images/' . $this->storageImage($request->file('image'));
        } else {
            if ($request->image_old_path != $cate->image) {
                $relativePath = str_replace('/storage/images/', '', $cate->image);
                Storage::disk('images')->delete($relativePath);
            }
            $filepath = $request->image_old_path;
        }

        $cate->name = $request->name;
        $cate->image = $filepath;
        $cate->status = $request->status;
        $cate->description = $request->description;
        $cate->save();

        return response()->json([
            "status" => true,
            "message" => "Update Category successful"
        ]);
    }

    public function deleteCategory(Request $request, $id)
    {
        $cate = Category::find($id);

        if (!$cate) {
            return response()->json([
                "status" => false,
                "message" => "Category not found"
            ], 404);
        }

        $has_products = $cate->products()->count();

        if ($has_products) {
            return response()->json([
                'status' => false,
                'message' => 'This category has products, please remove product first.'
            ], 409);
        }


        $cate->delete();

        return response()->json([
            "status" => true,
            "message" => "Delete Category successful"
        ]);
    }
}
