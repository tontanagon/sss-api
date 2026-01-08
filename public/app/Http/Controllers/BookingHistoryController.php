<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotification;
use App\Models\BookingHistory;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookingHistoryController extends Controller
{
    public function generateBookingNumber()
    {
        $today = Carbon::now()->format('Ymd');

        // ดึง booking ล่าสุดของวันนี้
        $last = BookingHistory::whereDate('created_at', Carbon::today())
            ->orderBy('booking_number', 'desc')
            ->first();

        if (!$last) {
            // ถ้าวันนี้ยังไม่มี booking เลย
            return $today . 'A000';
        }

        $lastBooking = $last->booking_number; // เช่น 20250827A015
        $datePart = substr($lastBooking, 0, 8); // 20250827
        $alphaPart = substr($lastBooking, 8, 1); // A
        $numPart = intval(substr($lastBooking, 9)); // 15

        if ($datePart !== $today) {
            // ถ้าเป็นวันใหม่ → รีเซ็ต
            return $today . 'A000';
        }

        if ($numPart < 999) {
            // เพิ่มเลขถัดไป
            $numPart++;
            return $today . $alphaPart . str_pad($numPart, 3, '0', STR_PAD_LEFT);
        } else {
            // ถ้าเลขเต็ม 999 → ขยับตัวอักษร
            $alphaPart = chr(ord($alphaPart) + 1); // A → B → C
            return $today . $alphaPart . '000';
        }
    }

    public function saveBooking(Request $request)
    {
        $MAX_BOOKING_PER_USER = 5;
        $MAX_ITEMS = 200;
        $message_booking = 'กรุณารออาจารย์ตรวจสอบและอนุมัติการจองของคุณ';
        $status = 'pending';

        $user = auth('sanctum')->user();
        $teachers = \App\Models\User::role('Teacher');
        $teacher = $teachers->pluck('name')->toArray();
        $teacher_list_to_validate = implode(',', $teacher);
        $check_is_teacher = in_array($user->name, $teacher);

        
        if ($check_is_teacher) {
            $request->merge(['teacher' => $user->name]);
            $status = 'approved';
            $message_booking = "กรุณารอเจ้าหน้าที่ตรวจสอบรายการจองและจัดของ";
        }

        $validator = Validator::make($request->all(), [
            "user_name"      => "required|string",
            "user_code"      => "required|string",
            "user_grade"     => "required|string",
            "phone_number"   => 'required|string',
            "return_at"      => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:' . now()->format('Y-m-d'),
                'before_or_equal:' . now()->addDays(30)->format('Y-m-d'),
            ],
            "subject"        => 'required|string',
            "teacher"        => 'required|string|in:' . $teacher_list_to_validate,
            "activity_name"  => 'required|string',
            "participants"   => 'required|integer',
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            return response()->json([
                'status' => false,
                'message' => $errorMessage,
            ], 400);
        }

        $booking_count = $user->bookinghistories()
            ->whereNotIn('status', ['completed', 'reject'])
            ->count();

        if ($booking_count >= $MAX_BOOKING_PER_USER) {
            return response()->json(['status' => false, 'message' => "รายการจองสามารถทำรายการได้สูงสุด {$MAX_BOOKING_PER_USER} รายการ"], 422);
        }

        $user_cart = $user->userCart;
        if (
            !$user_cart ||
            $user_cart->cart_items === '[]'
        ) {
            return response()->json(['status' => false, 'message' => 'ไม่พบสินค้าในตะกร้า'], 404);
        }

        $cart_items = json_decode($user_cart->cart_items, true);
        if (collect($cart_items)->sum('quantity') > $MAX_ITEMS) {
            return response()->json(['status' => false, 'message' => "จำนวนวัสดุ-อุปกรณ์ทั้งหมด สามารถยืมได้สูงสุด {$MAX_ITEMS} ต่อ 1 การจอง"], 422);
        }

        $product = Product::get();
        $productMap = $product->keyBy('id');

        $insufficient_stock = [];

        foreach ($cart_items as $item) {
            $product_id = $item['id'];
            $required_quantity = $item['quantity'];

            if (!isset($productMap[$product_id]) || $productMap[$product_id]->stock < $required_quantity) {
                $insufficient_stock[] = [
                    'id' => $product_id,
                    'name' => $item['name'],
                    'available' => $productMap[$product_id]->stock ?? 0,
                    'required' => $required_quantity
                ];
            }
        }

        if (!empty($insufficient_stock)) {
            $messages = [];

            foreach ($insufficient_stock as $item) {
                $messages[] = $item['name'] . ' คงเหลือ ' . $item['available'];
            }

            return response()->json([
                'status' => false,
                'message' => "มีวัสดุ-อุปกรณ์ที่ไม่เพียงพอ:\n" . implode("\n", $messages),
                'insufficient_items' => $insufficient_stock
            ], 422);
        }

        DB::beginTransaction();
        try {
            $booking = $user->bookingHistories()->create([
                'booking_number' => $this->generateBookingNumber(),
                'user_name' => $user->name,
                'user_code' => $request->user_code,
                'user_grade' => $request->user_grade,
                'phone_number' => $request->phone_number,
                'return_at' => $request->return_at,
                'subject' => $request->subject,
                'teacher' => $request->teacher,
                'status' => $status,
                'activity_name' => $request->activity_name,
                'participants' => $request->participants,
            ]);

            foreach ($cart_items as $cart) {
                $productStock = $booking->productStockHistory()->create([
                    'product_id' => $cart['id'],
                    'stock' => $cart['stock'],
                    'type' => 'decrease',
                    'add_type' => 'booking',
                    'by_user_id' => $user->id,
                    'before_stock' => $cart['stock'],
                    'after_stock' => $cart['stock'] - $cart['quantity'],
                ]);

                $booking->itemBookingHistories()->create([
                    'product_id' => $cart['id'],
                    'product_name' => $cart['name'],
                    'product_stock_history_id' => $productStock->id,
                    'product_category' => json_encode($cart['category']),
                    'product_type' => $cart['type'],
                    'product_unit' => $cart['unit'],
                    'product_quantity' => $cart['quantity'],
                    'product_quantity_return' => $cart['quantity'],
                    'status' => 'pending'
                ]);

                Product::where('id', $cart['id'])->decrement('stock', $cart['quantity']);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'ไม่สามารถเพิ่มได้ กรุณาติดต่อผู้ดูแล', 'error' => $e], 500);
        }

        if (isset($booking)) {
            $user_cart->delete();
            if ($check_is_teacher) {
                $admins = \App\Models\User::role('Administrator')->get();
                ////แจ้งเตือน @admin อาจารทำการจอง
                $noti = [
                    'subject' => "แจ้งเตือนการจอง #{$booking->booking_number} อาจารย์ทำการจอง",
                    'display_button' => 'เปิดรายการจอง',
                    'title' => 'กรุณาตรวจสอบรายการจองจากอาจารย์',
                    'style_text' => 'text-[#FCB500]',
                    'message' => "รายการของ {$booking->user_name} ได้รับการอนุมัติเรียบร้อยแล้ว กรุณาตรวจสอบรายละเอียดและจัดเตรียมของตามคำขอ",
                    'url' => "/medadm/requests/{$booking->id}",
                    'booking_data' => $booking,
                ];
                foreach ($admins as $admin) {
                    SendNotification::dispatch($admin, $noti);
                }
            } else {
                ////แจ้งเตือน @นศ ยืนยันการจอง
                $noti_student = [
                    'subject' => "แจ้งเตือนการจอง #{$booking->booking_number} ทำรายการสำเร็จ",
                    'display_button' => "ดูรายละเอียดการจอง",
                    'title' => 'รายการจองของคุณถูกบันทึกเรียบร้อยแล้ว',
                    'style_text' => 'text-[#FCB500]',
                    'message' => "รายการจองของคุณได้ถูกบันทึกเข้าระบบเรียบร้อยแล้ว ขณะนี้อยู่ระหว่างการรออนุมัติจากอาจารย์",
                    'url' => "/booking-history/{$booking->id}",
                    'booking_data' => $booking,
                ];

                SendNotification::dispatch($user, $noti_student);

                ////แจ้งเตือน @อาจารย์ นักศึกษาทำการจองสำเร็จ
                $noti_teacher = [
                    'subject' => "แจ้งเตือนการจอง #{$booking->booking_number} นักศึกษาทำการจอง",
                    'display_button' => "ตรวจสอบคำขอ",
                    'title' => 'กรุณาตรวจสอบและอนุมัติรายการจอง',
                    'style_text' => 'text-[#FCB500]',
                    'message' => "มีรายการคำขอจาก {$booking->user_name} ที่รอการอนุมัติของท่าน กรุณาเข้าสู่ระบบเพื่อตรวจสอบและดำเนินการต่อ",
                    'url' => "/medtch/{$booking->id}",
                    'booking_data' => $booking,
                ];
                SendNotification::dispatch($teachers->where('name', $booking->teacher)->first(), $noti_teacher);
            }

            $response = [
                'status' => true,
                'message' => $message_booking,
            ];
            return response()->json($response, 201);
        }
    }

    public function userBookingHistoryById($id)
    {
        $user = auth('sanctum')->user();
        $booking = $user->BookingHistories()->with([
            'itemBookingHistories',
            'bookingStatusHistories' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }
        ])->find($id);

        return response()->json($booking, 200);
    }

    public function userBookingHistoryAll(Request $request)
    {
        $paginate = 10;
        $status = ['pending', 'approved', 'packed', 'inuse', 'overdue', 'returned', 'reject', 'completed', 'incomplete'];
        $search_text = '';
        if (isset($request->limit)) {
            $paginate = $request->limit;
        }
        if (isset($request->search_status)) {
            $status = explode(',', $request->search_status);
        }
        if (isset($request->search_text)) {
            $search_text = $request->search_text;
        }

        $user = auth('sanctum')->user();
        $booking_list = $user->bookingHistories()
            ->where(function ($query) use ($search_text) {
                $query->where('activity_name', 'like', "%{$search_text}%")
                    ->orWhere('booking_number', 'like', "%{$search_text}%");
            })
            ->with('itemBookingHistories')
            ->whereIn('status', $status)
            ->orderBy('created_at', 'desc');


        // dd($request->all());->paginate($paginate);

        return response()->json($booking_list->paginate($paginate), 200);
    }

    public function userBookingHistoryBorrow(Request $request)
    {
        $paginate = 10;
        $status = ['pending', 'approved', 'inuse', 'overdue', 'incomplete'];
        $search_text = '';
        if (isset($request->limit)) {
            $paginate = $request->limit;
        }
        if (isset($request->search_status)) {
            $status = explode(',', $request->search_status);
        }
        if (isset($request->search_text)) {
            $search_text = $request->search_text;
        }

        $user = auth('sanctum')->user();
        $booking_list = $user->bookingHistories()
            ->with('itemBookingHistories')
            ->where(function ($query) use ($search_text) {
                $query->where('activity_name', 'like', "%{$search_text}%")
                    ->orWhere('booking_number', 'like', "%{$search_text}%");
            })
            ->whereIn('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate($paginate);

        return response()->json($booking_list, 200);
    }

    public function userBookingHistoryReturned(Request $request)
    {
        $paginate = 10;
        $search_text = '';
        if (isset($request->limit)) {
            $paginate = $request->limit;
        }
        if (isset($request->search_text)) {
            $search_text = $request->search_text;
        }

        $user = auth('sanctum')->user();
        $booking_list = $user->bookingHistories()
            ->with('itemBookingHistories')
            ->where('activity_name', 'like', "%{$search_text}%")
            ->where('status', 'returned')
            ->orderBy('created_at', 'desc')
            ->paginate($paginate);

        return response()->json($booking_list, 200);
    }

    public function confirmPickup(Request $request, $id)
    {
        $user = auth('sanctum')->user();
        if ($user->hasRole('Administrator')) {
            $booking = BookingHistory::with('itemBookingHistories')->find($id);
        } else {
            $booking = $user->bookingHistories()->with('itemBookingHistories')->find($id);
        }

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        // if (!$booking->status === 'packed') {
        //     return response()->json([
        //         'status' => false,
        //         'message' => "Booking can't change to inuse",
        //     ], 400);
        // }
        DB::beginTransaction();

        try {
            $booking->update(['status' => 'inuse', "remark" => $request->remark ?? null]);
            foreach ($booking->itemBookingHistories as $item) {
                $item->update(['status' => 'received']);
            }

            $date_return = \Carbon\Carbon::parse($booking->return_at)->thaidate('j F Y');
            /////แจ้งเตือน @นศ แจ้งเตือนรับวัสดุ
            $noti = [
                'subject' => "แจ้งเตือนการยืม #{$booking->booking_number} ยืนยันการรับวัสดุ",
                'display_button' => "ดูรายการที่รับ",
                'title' => "ระบบบันทึกการรับวัสดุของคุณเรียบร้อยแล้ว",
                'style_text' => 'text-[#3FB0D9]',
                'message' => "คุณได้รับวัสดุเรียบร้อยแล้ว กรุณาคืนภายในวันที่ : {$date_return}",
                'url' => "/booking-history/{$booking->id}",
                'booking_data' => $booking,
            ];
            SendNotification::dispatch($booking->user, $noti);


            $admins = \App\Models\User::role('Administrator')->get();
            ////แจ้งเตือน @admin แจ้งเตือนเมือนักศึกษารับวัสดุไปเเล้ว
            $noti_admin = [
                'subject' => "แจ้งเตือนการยืม #{$booking->booking_number} นักศึกษารับวัสดุแล้ว",
                'display_button' => "ตรวจสอบรายการ",
                'title' => 'ยืนยันรับของเเล้ว',
                'style_text' => 'text-[#3FB0D9]',
                'message' => "รายการของ {$booking->user_name} ได้ถูกใช้งานเรียบร้อยแล้ว",
                'url' => "/medadm/requests/{$booking->id}",
                'booking_data' => $booking,
            ];
            foreach ($admins as $admin) {
                SendNotification::dispatch($admin, $noti_admin);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error("Booking confirm error: {$e->getMessage()}", ['exception' => $e]);

            return response()->json([
                'status' => false,
                'message' => 'ระบบขัดข้อง กรุณาติดต่อผู้ดูแล',
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => "Pickup successful",
        ], 200);
    }

    public function confirmReturn(Request $request)
    {
        $user = auth('sanctum')->user();
        if ($user->hasRole('Administrator')) {
            $booking = BookingHistory::with('itemBookingHistories')->find($request->id);
        } else {
            $booking = $user->bookingHistories()->with('itemBookingHistories')->find($request->id);
        }

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $booking->update(['status' => 'returned','remark' => $request->remark ?? null]);
            
            foreach ($booking->itemBookingHistories as $index => $item) {
                if ($request->item_booking_histories) {
                    $item->update(['product_quantity_return' => $request->item_booking_histories[$index]["product_quantity_return"]]);
                } else {
                    //for admin change status
                    $item->update(['product_quantity_return' => $item->product_quantity]);
                }
            }

            $admins = \App\Models\User::role('Administrator')->get();
            ////แจ้งเตือน @admin แจ้งเตือนเมือนักศึกษาคืนวัสดุ
            $noti_admin = [
                'subject' => "แจ้งเตือนการคืน #{$booking->booking_number} นักศึกษาคืนวัสดุแล้ว",
                'display_button' => "ตรวจสอบรายการ",
                'title' => "กรุณาตรวจสอบรายการคืนวัสดุ",
                'style_text' => 'text-[#F20D6C]',
                'message' => "รายการของ {$booking->user_name} ถูกส่งคืนแล้ว กรุณาตรวจสอบ",
                'url' => "/medadm/requests/{$booking->id}",
                'booking_data' => $booking,
            ];
            foreach ($admins as $admin) {
                SendNotification::dispatch($admin, $noti_admin);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error("Booking confirm error: {$e->getMessage()}", ['exception' => $e]);

            return response()->json([
                'status' => false,
                'message' => 'ระบบขัดข้อง กรุณาติดต่อผู้ดูแล',
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => "Return successful",
        ], 200);
    }
}
