<?php

namespace App\Http\Controllers\CoreConfigs;

use App\Http\Controllers\Controller;
use App\Models\CoreConfigs\Subject;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    public function getSubject(Request $request)
    {
        $paginate = ($request->limit && $request->limit !== 'null') ? (int)$request->limit : 10;
        $search_text = ($request->search_text && $request->search_text !== 'null') ? $request->search_text : null;

        if ($search_text && $search_text != "") {
            $subject = Subject::where(function ($sub) use ($search_text) {
                $sub->where('name', 'like', "%{$search_text}%")
                    ->orWhere('code', 'like', "%{$search_text}%");
            })->paginate($paginate);
        } else {
            $subject = Subject::paginate($paginate);
        }


        if (!$subject) {
            return response()->json([
                "status" => false,
                "message" => "Subject not found"
            ], 404);
        }

        return response()->json($subject, 200);
    }

    public function getSubjectById(Request $request, $id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                "status" => false,
                "message" => "Subject not found"
            ], 404);
        }

        return response()->json($subject, 200);
    }

    public function createSubject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "code" => "required|string|unique:core_configs,code",
            "name" => "nullable|string",
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

        Subject::create([
            "code" => $request->code,
            "name" => $request->name,
            "status" => $request->status,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Create subject successful.'
        ], 201);
    }

    public function updateSubject(Request $request, $id)
    {
        $subject = Subject::find($id);

        $validator = Validator::make($request->all(), [
            "code" => "required|string|unique:core_configs,code,".$subject->id,
            "name" => "nullable|string",
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

        if (!$subject) {
            return response()->json([
                "status" => false,
                "message" => "Subject not found"
            ], 404);
        }

        $subject->update([
            'code' => $request->code,
            'name' => $request->name,
            'status' => $request->status,
        ]);

        return response()->json([
            "status" => true,
            "message" => "Update subject successful"
        ], 200);
    }

    public function deleteSubject(Request $request, $id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                "status" => false,
                "message" => "Subject not found"
            ], 404);
        }

        $subject->delete();

        return response()->json([
            "status" => true,
            "message" => "Delete subject successful."
        ], 200);
    }
}
