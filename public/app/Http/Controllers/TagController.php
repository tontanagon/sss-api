<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tag;

use Illuminate\Support\Facades\Validator;


class TagController extends Controller
{
    public function getTag()
    {
        $pagination = $request->limit ?? 10;
        $search_text = $request->search_text ?? '';

        $tags = Tag::where('name', 'like', "%{$search_text}%")->paginate($pagination);

        return response()->json($tags, 200);
    }

    public function getTagById($id)
    {
        $tags = Tag::find($id);

        if (!$tags) {
            return response()->json([
                'status' => false,
                'message' => 'Tag on this id is not found',
            ], 404);
        }

        return response()->json($tags);
    }

    public function createTag(Request $request)
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
            return response()->json($response, );
        }
        Tag::create([
            'name' => $request->name,
            'status' => $request->status,
            'description' => $request->description,
        ]);

        return response()->json([
            "status" => true,
            "message" => "Create Tag successful"
        ]);
    }

    public function updateTag(Request $request)
    {
        $tag = Tag::find($request->id);

        if (!$tag) {
            return response()->json([
                "status" => false,
                "message" => "Tag not found"
            ], 404);
        }

        $tag->name = $request->name;
        $tag->status = $request->status;
        $tag->description = $request->description;
        $tag->save();

        return response()->json([
            "status" => true,
            "message" => "Update Tag successful"
        ]);
    }

    public function deleteTag(Request $request)
    {
        $tag = Tag::find($request->id);

        if (!$tag) {
            return response()->json([
                "status" => false,
                "message" => "Tag not found"
            ], 404);
        }

        $has_products = $tag->products()->count();

        if ($has_products) {
            return response()->json([
                'status' => false,
                'message' => 'This tag has products, please remove product first.'
            ], 409);
        }


        $tag->delete();
        return response()->json([
            "status" => true,
            "message" => "Delete Tag successful"
        ]);
    }
}
