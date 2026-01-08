<?php

namespace App\Http\Controllers\CoreConfigs;

use App\Http\Controllers\Controller;
use App\Models\CoreConfigs\Banner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    private function storageImage($image)
    {
        $date = Carbon::now()->format('Ymd_His');
        $extension = $image->getClientOriginalExtension();
        $filename = "banner_{$date}." . $extension;

        return Storage::disk('images')->putFileAs('core_configs', $image, $filename);
    }

    public function getBanner(Request $request)
    {
        $paginate = ($request->limit && $request->limit !== 'null') ? (int)$request->limit : 10;
        $search_text = ($request->search_text && $request->search_text !== 'null') ? $request->search_text : null;
        
        if ($search_text && $search_text != "") {
            $banner = Banner::where('name', 'like', "%{$search_text}%")->paginate($paginate);
        } else {
            $banner = Banner::paginate($paginate);
        }


        if (!$banner) {
            return response()->json([
                "status" => false,
                "message" => "Banner not found"
            ], 404);
        }

        return response()->json($banner, 200);
    }

    public function getBannerById(Request $request, $id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json([
                "status" => false,
                "message" => "Banner not found"
            ], 404);
        }

        return response()->json($banner, 200);
    }

    public function createBanner(Request $request)
    {
        $filepath = null;
        $validator = Validator::make($request->all(), [
            "name" => "nullable|string",
            "title" => "nullable|string",
            "description" => 'nullable|string',
            "cover" => 'nullable|file|mimes:jpg,jpeg,png',
            "status" => "required|string|in:active,inactive",
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status' => false,
                'message' => $errorMessage,
            ];
            return response()->json($response, 400);
        }

        if ($request->file('cover')) {
            if (isset($banner->cover)) {
                $relativePath = str_replace('/storage/images/', '', $banner->cover);
                Storage::disk('images')->delete($relativePath);
            }
            $filepath = '/storage/images/' . $this->storageImage($request->file('cover'));
        }

        $banner = Banner::create([
            // "code" => random_int(1,5),
            "name" => $request->name,
            "title" => $request->title,
            "description" => $request->description,
            "cover" => $filepath,
            "status" => $request->status,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Create banner successful.'
        ], 201);
    }

    public function updateBanner(Request $request, $id)
    {
        $banner = Banner::find($id);
        $filepath = null;

        $validator = Validator::make($request->all(), [
            "name" => "nullable|string",
            "title" => "nullable|string",
            "description" => 'nullable|string',
            "cover" => 'nullable|file|mimes:jpg,jpeg,png',
            "status" => "required|string|in:active,inactive",
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status' => false,
                'message' => $errorMessage,
            ];
            return response()->json($response, 400);
        }

        if (!$banner) {
            return response()->json([
                "status" => false,
                "message" => "Banner not found"
            ], 404);
        }

        if ($request->file('cover')) {
            if (isset($banner->cover)) {
                $relativePath = str_replace('/storage/images/', '', $banner->cover);
                Storage::disk('images')->delete($relativePath);
            }
            $filepath = '/storage/images/' . $this->storageImage($request->file('cover'));
        } else {
            if ($request->image_old_path != $banner->cover) {
                $relativePath = str_replace('/storage/images/', '', $banner->cover);
                Storage::disk('images')->delete($relativePath);
            }
            $filepath = $request->image_old_path;
        }

        $banner->update([
            'name' => $request->name,
            'title' => $request->title,
            'description' => $request->description,
            'cover' => $filepath,
            'status' => $request->status,
        ]);

        return response()->json([
            "status" => true,
            "message" => "Update banner successful"
        ], 200);
    }

    public function deleteBanner(Request $request, $id)
    {
        $banner = Banner::find($id);
        $relativePath = str_replace('/storage/images/', '', $banner->cover);

        if (!$banner) {
            return response()->json([
                "status" => false,
                "message" => "Banner not found"
            ], 404);
        }

        if ($banner->cover) {
            Storage::disk('images')->delete($relativePath);
        }
        $banner->delete();

        return response()->json([
            "status" => true,
            "message" => "Delete banner successful."
        ], 200);
    }
}
