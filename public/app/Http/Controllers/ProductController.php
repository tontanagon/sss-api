<?php

namespace App\Http\Controllers;

use App\Exports\ProductExportSheet;
use App\Models\BookingHistory;
use App\Models\Category;
use App\Models\CoreConfigs\Subject;
use App\Models\Product;
use App\Models\ProductStockHistory;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    private function storageImage($image)
    {
        $date = Carbon::now()->format('Ymd_His');
        $extension = $image->getClientOriginalExtension();
        $filename = "product_{$date}." . $extension;

        return Storage::disk('images')->putFileAs('product', $image, $filename);
    }

    private function addTags($product, $tags)
    {
        $existingTags = Tag::whereIn('name', $tags)->pluck('id', 'name')->toArray();

        $newTagIds = [];

        foreach ($tags as $tagName) {
            if (!isset($existingTags[$tagName])) {
                $tag = Tag::create(['name' => $tagName, 'status' => 'active']);
                $newTagIds[] = $tag->id;
            }
        }

        // รวม id ทั้งหมด (ของเก่า + ของใหม่)
        $allTagIds = array_merge(array_values($existingTags), $newTagIds);

        // sync กับ product
        return $product->tags()->sync($allTagIds);
    }

    public function getProduct(Request $request)
    {
        $paginate = $request->limit ?? 10;

        $search_status = $request->search_status;
        if (is_string($search_status)) {
            $search_status = json_decode($search_status, true);
        }

        // Default ถ้าไม่มีค่า
        $search_status = $search_status ?? [
            'category' => [],
            'type' => []
        ];

        $search_text = $request->search_text ?? '';

        $page = $request->page ?? 1;

        $products = Product::with('categories:id,name', 'type:id,name')
            ->when(
                !empty($search_status['category']),
                fn($query) =>
                $query->whereHas(
                    'categories',
                    fn($q) =>
                    $q->whereIn('id', $search_status['category'])
                )
            )
            ->when(
                !empty($search_status['type']),
                fn($query) =>
                $query->whereHas(
                    'type',
                    fn($q) =>
                    $q->whereIn('id', $search_status['type'])
                )
            )
            ->where(function ($q) use ($search_text) {
                $q->where('name', 'like', "%{$search_text}%")
                    ->orWhere('code', 'like', "%{$search_text}%");
            })
            ->paginate($paginate, ['*'], 'page', $page);

        return response()->json($products, 200);
    }


    public function getProductById($id)
    {
        $data = [];
        $products = Product::with('categories', 'type', 'tags')->find($id);

        if (!$products) {
            $response = [
                'status' => false,
                'message' => 'Product not found.',
            ];
            return response()->json($response, 404);
        }
        $data['id'] = $products->id;
        $data['name'] = $products->name;
        $data['code'] = $products->code;
        $data['description'] = $products->description;
        $data['image'] = $products->image;
        $data['status'] = $products->status;
        $data['stock'] = $products->stock;
        $data['unit'] = $products->unit;
        $data['type'] = $products->type->name;
        $data['type_id'] = $products->type_id;

        $cate_id = [];
        foreach ($products->categories as $cate) {
            $cate_id[] = $cate->id;
        }
        $data['category_ids'] = $cate_id;

        $tags = [];
        foreach ($products->tags as $tag) {
            $tags[] = $tag->name;
        }
        $data['tags'] = $tags;

        return response()->json($data, 201);
    }

    public function createProduct(Request $request)
    {
        // $user = auth('sanctum')->user();
        $user = User::find(1); // temp for test admin
        $filepath = '';
        $validator = Validator::make($request->all(), [
            "name" => "required|string",
            "code" => "required|string",
            "status" => "required|string|in:active,inactive",
            "image" => 'nullable|file|mimes:jpg,jpeg,png',
            "descritpion" => 'nullable|string',
            "stock" => 'required|integer',
            "unit" => 'nullable|string',
            "type_id" => 'required|string',
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status' => false,
                'message' => $errorMessage,
            ];
            return response()->json($response, 400);
        }

        DB::beginTransaction();
        try {
            if ($request->file('image')) {
                $filepath = $this->storageImage($request->image);
            }
            $product = Product::create([
                'name' => $request->name,
                'code' => $request->code,
                'image' => '/storage/images/' . $filepath,
                'status' => $request->status,
                'description' => $request->description,
                'stock' => $request->stock,
                'unit' => $request->unit,
                'type_id' => $request->type_id,
            ]);

            $product->productStockHistories()->create([
                'stock' => 0,
                'type' => 'increase',
                'add_type' => 'manual',
                'by_user_id' => $user->id,
                'before_stock' => 0,
                'after_stock' => $request->stock,
                'remark' => 'Create product',
            ]);

            if ($request->tags) {
                $tagArray = explode(',', $request->tags);
                $this->addTags($product, $tagArray);
            }

            $categoryArray = explode(',', $request->category);
            $product->categories()->attach($categoryArray);
            DB::commit();
        } catch (\Throwable $e) {
            $relativePath = str_replace('/storage/images/', '', $filepath);
            Storage::disk('images')->delete($relativePath);
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'ไม่สามารถเพิ่มได้ กรุณาติดต่อผู้ดูแล', 'error' => $e], 500);
        }

        return response()->json([
            "status" => true,
            "message" => "Create product successful"
        ], 200);
    }

    public function updateProduct(Request $request, $id)
    {
        $product = Product::find($id);
        $filepath = '';

        if (!$product) {
            return response()->json([
                "status" => false,
                "message" => "Product not found"
            ], 404);
        }

        if ($request->file('image')) {
            if (isset($product->image)) {
                $relativePath = str_replace('/storage/images/', '', $product->image);
                Storage::disk('images')->delete($relativePath);
            }
            $filepath = '/storage/images/' . $this->storageImage($request->file('image'));
        } else {
            if ($request->image_old_path != $product->image) {
                $relativePath = str_replace('/storage/images/', '', $product->image);
                Storage::disk('images')->delete($relativePath);
            }
            $filepath = $request->image_old_path;
        }

        DB::beginTransaction();
        try {
            $product->name = $request->name;
            $product->code = $request->code;
            $product->image = $filepath;
            $product->status = $request->status;
            $product->description = $request->description;
            $product->unit = $request->unit;
            $product->type_id = $request->type_id;
            $product->save();

            if ($request->tags) {
                $tagArray = explode(',', $request->tags);
                $this->addTags($product, $tagArray);
            }

            $categoryArray = explode(',', $request->category);
            $product->categories()->sync($categoryArray);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $product->image = $filepath;
            $product->save();
            return response()->json(['status' => false, 'message' => 'ไม่สามารถเพิ่มได้ กรุณาติดต่อผู้ดูแล', 'error' => $e], 500);
        }

        return response()->json([
            "status" => true,
            "message" => "Update product successful"
        ]);
    }

    public function updateProductStock(Request $request)
    {
        // $user = auth('sanctum')->user();
        $user = User::find(1); // temp for test admin

        $product = Product::find($request->id);
        // id: formData.id,
        // before_stock: formData.before_stock,
        // after_stock: formData.after_stock,
        // remark: formData.description,
        if (!$product) {
            return response()->json([
                "status" => false,
                "message" => "Product not found"
            ], 404);
        }

        if ($product->stock != $request->before_stock) {
            return response()->json([
                "status" => false,
                "message" => "Stock has changed, please reload the page before updating."
            ], 409);
        }

        if ($request->stock_change == 0) {
            return response()->json([
                "status" => false,
                "message" => "Stock not change"
            ], 422);
        }
        if ($request->stock_change > 0) {
            $type = 'increase';
        } else {
            $type = 'decrease';
        }
        $before_stock = $request->before_stock;


        DB::beginTransaction();
        try {
            $product->productStockHistories()->create([
                'stock' => $before_stock,
                'type' => $type,
                'add_type' => 'manual',
                'by_user_id' => $user->id,
                'before_stock' => $before_stock,
                'after_stock' => $before_stock + $request->stock_change,
                'remark' => $request->remark,
            ]);
            $product->stock = $before_stock + $request->stock_change;
            $product->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'ไม่สามารถเพิ่มได้ กรุณาติดต่อผู้ดูแล', 'error' => $e], 500);
        }

        return response()->json([
            "status" => true,
            "message" => "Update product stock successful"
        ], 200);
    }

    public function deleteProduct($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                "status" => false,
                "message" => "Product not found"
            ], 404);
        }

        $product->delete();

        return response()->json([
            "status" => true,
            "message" => "Delete product successful"
        ]);
    }

    public function getOptionCategory()
    {
        $cate = \App\Models\Category::select('id', 'name')->get();
        return response()->json($cate, 200);
    }

    public function getOptionType()
    {
        $type = \App\Models\Type::select('id', 'name')->get();
        return response()->json($type, 200);
    }

    public function getOptionTag()
    {
        $tags = Tag::select('id', 'name')->get();
        return response()->json($tags, 200);
    }

    public function getOptionSubject()
    {
        $subject = Subject::select('code', 'name')->get();
        $data = [];
        foreach ($subject as $sub) {
            $data[] = [
                'value' => $sub->code,
                'label' => $sub->display_name,
            ];
        }

        return response()->json($data, 200);
    }

    public function getOptionTeacher()
    {
        $subject = User::role('Teacher')->select('id', 'name')->get();
        $data = [];
        foreach ($subject as $sub) {
            $data[] = [
                'value' => $sub->id,
                'label' => $sub->name,
            ];
        }

        return response()->json($data, 200);
    }

    public function getProductHistory(Request $request, $id)
    {
        $data = [];
        $product = Product::with('type', 'categories')->where('id', $id)->first();

        $product_stock_history = ProductStockHistory::with('bookingHistory', 'user')
            ->where('product_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        $data['product_info'] = [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'image' => $product->image,
            'status' => $product->status,
            'stock' => $product->stock,
            'unit' => $product->unit,
            'type' => $product->type->name,
            'category' => $product->categories[0]->name ?? '',
            'updated_at' => $product->updated_at,
        ];

        foreach ($product_stock_history as $key => $value) {
            $data['product_history'][] = [
                'type' => $value->type,
                'add_type' => $value->add_type,
                'user_name' => $value->user->name,
                'booking_id' => $value->booking_history_id,
                'booking_number' => $value->bookingHistory->booking_number ?? '',
                'before_stock' => $value->before_stock,
                'after_stock' => $value->after_stock,
                'created_at' => $value->created_at,
            ];
        }


        return response()->json($data, 200);
    }

    public function exportProductXlsx(Request $request)
    {
        $data = [];
        $category = $request->category ? explode(',', $request->category) : [];
        $teacher  = $request->teacher ? explode(',', $request->teacher) : [];
        $subject  = $request->subject ? explode(',', $request->subject) : [];
        $from     = $request->fromDate ?? null;
        $to       = $request->toDate ?? null;
        $teacher_name = User::whereIn('id', $teacher)->pluck('name')->toArray();

        $query = Product::query();
        $query->with('itemBookingHistories.bookingHistory', 'categories', 'type');

        if (!empty($category)) {
            $query->whereHas('categories', function ($q) use ($category) {
                $q->whereIn('categories.id', $category);
            });
        }

        $query = $query->get();

        foreach ($query as $item) {
            //count itembookinghistories->product_quantity

            $completedHistories = $item->itemBookingHistories->filter(function ($history) use ($from, $to, $teacher_name, $subject) {
                $booking = $history->bookingHistory;

                if (!$booking) return false;

                $createdAt = $booking->created_at;

                if ($from && $createdAt < $from) return false;
                if ($to && $createdAt > $to) return false;

                if (!empty($teacher_name) && !in_array($booking->teacher, $teacher_name)) return false;
                if (!empty($subject) && !in_array($booking->subject, $subject)) return false;

                return $booking->status === 'completed';
            });
            $item->item_count = $completedHistories->sum('product_quantity');
            $item->booking_count = $completedHistories->count();
            $item->booking_count_return = $completedHistories
                ->where('product_type', 'ยืมคืน')
                ->sum('product_quantity_return');

            $data[] = [
                'category' => $item->categories[0]->name ?? '',
                'code' => $item->code,
                'name' => $item->name,
                'stock' => $item->stock,
                'item_count' => $item->item_count,
                'booking_count' => $item->booking_count,
                'booking_lost' =>  $item->booking_count_return - $item->booking_count,
            ];
        }

        ////filename
        // $category_name = Category::whereIn('id', $category)->pluck('name')->toArray();
        // $subject_name = Subject::whereIn('code', $subject)->pluck('name')->toArray();
        // $category_path = !empty($category_name) ? implode('_', $category_name) : 'all_categories';
        // $subject_path = !empty($subject_name) ? implode('_', $subject_name) : 'all_subjects';

        $category_path = !empty($category) ? implode('_', $category) : 'all_categories';
        $teacher_path = !empty($teacher_name) ? implode('_', $teacher_name) : 'all_teachers';
        $subject_path = !empty($subject) ? implode('_', $subject) : 'all_subjects';
        $date_path = Carbon::now()->format('Ymd_His');

        $data_filename = "{$category_path}_{$teacher_path}_{$subject_path}_{$date_path}.xlsx";

        return Excel::download(new ProductExportSheet($data), $data_filename);
    }

    public function testEmail()
    {
        $booking = BookingHistory::find(112);
        $return_date = \Carbon\Carbon::parse($booking->return_at)->thaidate('j F Y');
        $noti = [
            'subject' => "แจ้งเตือนการยืม #{$booking->booking_number} นักศึกษารับวัสดุแล้ว",
            'display_button' => "ตรวจสอบรายการ",
            'title' => 'ยืนยันรับของเเล้ว',
            'style_text' => 'text-[#3FB0D9]',
            'message' => "รายการของ {$booking->user_name} ได้ถูกใช้งานเรียบร้อยแล้ว",
            'url' => "/medadm/requests/{$booking->id}",
            'booking_data' => $booking,
        ];
        return view('mail.custom_email', [
            "data" => $noti
        ]);
    }
}
