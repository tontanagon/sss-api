<?php

namespace App\Http\Controllers;

use App\Models\CoreConfigs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CoreConfigsController extends Controller
{
    public function storageImage($image)
    {
        $date = Carbon::now()->format('Ymd_His');
        $extension = $image->getClientOriginalExtension();
        $filename = "core_configs_{$date}." . $extension;

        return Storage::disk('images')->putFileAs('core_configs', $image, $filename);
    }

    public function getCoreConfigs()
    {
        $core_configs = CoreConfigs::get();

        if (!$core_configs) {
            return response()->json([
                "status" => false,
                "message" => "Core configs not found"
            ], 404);
        }

        return response()->json($core_configs, 200);
    }

    public function getCoreConfigsByCode($code)
    {
        $core_configs = CoreConfigs::where('code', $code)->first();

        if (!$core_configs) {
            return response()->json([
                "status" => false,
                'code' => $code,
                "message" => "Core configs not found"
            ], 404);
        }

        return response()->json($core_configs, 200);
    }

    public function createCoreConfigs(Request $request)
    {
        $filepath = null;
        $validator = Validator::make($request->all(), [
            "name" => "nullable|string",
            'code' => 'required|string|unique:core_configs,code',
            "link" => "nullable|string",
            "cover" => 'nullable|file|mimes:jpg,jpeg,png',
            "title" => 'nullable|string',
            "description" => 'nullable|string',
            "content" => 'nullable|string',
            "group" => 'nullable|string',
            "category" => 'nullable|string',
            "status" => 'nullable|string|in:active,inactive',
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
            $filepath = $this->storageImage($request->cover);
        }

        $core_configs = CoreConfigs::create([
            'name' => $request->name,
            'code' => $request->code,
            'link' => $request->link,
            'cover' => $filepath ? '/storage/images/' . $filepath : null,
            'title' => $request->title,
            'description' => $request->description,
            'content' => $request->content,
            'group' => $request->group,
            'category' => $request->category,
            'status' => $request->status,
        ]);

        return response()->json([
            "status" => true,
            "message" => "Create core_configs successful"
        ], 200);
    }

    public function updateCoreConfigs($code, Request $request)
    {
        $core_configs = CoreConfigs::where('code', $code)->first();
        $filepath = '';
        $validator = Validator::make($request->all(), [
            "name" => "nullable|string",
            // 'code' => 'required|string|unique:core_configs,code,' . $core_configs->id,
            "link" => "nullable|string",
            "cover" => 'nullable|file|mimes:jpg,jpeg,png',
            "title" => 'nullable|string',
            "description" => 'nullable|string',
            "content" => 'nullable|string',
            // "group" => 'nullable|string',
            // "category" => 'nullable|string',
            "status" => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status' => false,
                'message' => $errorMessage,
            ];
            return response()->json($response, 400);
        }

        if (!$core_configs) {
            return response()->json([
                "status" => false,
                "message" => "CoreConfigs not found"
            ], 404);
        }

        if ($request->file('cover')) {
            if (isset($core_configs->cover)) {
                $relativePath = str_replace('/storage/images/', '', $core_configs->cover);
                Storage::disk('images')->delete($relativePath);
            }
            $filepath = '/storage/images/' . $this->storageImage($request->file('cover'));
        } else {
            if ($request->image_old_path != $core_configs->cover) {
                $relativePath = str_replace('/storage/images/', '', $core_configs->cover);
                Storage::disk('images')->delete($relativePath);
            }
            $filepath = $request->image_old_path;
        }

        $core_configs->update([
            'name' => $request->name,
            // 'code' => $request->code,
            'link' => $request->link,
            'cover' => $filepath,
            'title' => $request->title,
            'description' => $request->description,
            'content' => $request->content === "<p><br></p>" ? null : $request->content,
            // 'group' => $request->group,
            // 'category' => $request->category,
            'status' => $request->status,
        ]);

        return response()->json([
            "status" => true,
            "message" => "CoreConfigs update successful"
        ], 200);
    }

    public function deleteCoreConfigs($code)
    {
        $core_configs = CoreConfigs::where('code', $code)->first();

        if (!$core_configs) {
            return response()->json([
                "status" => false,
                'code' => $code,
                "message" => "CoreConfigs not found"
            ], 404);
        }

        $core_configs->delete();

        return response()->json([
            "status" => true,
            "message" => "CoreConfigs delete successful"
        ], 200);
    }
}
