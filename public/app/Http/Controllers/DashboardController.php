<?php

namespace App\Http\Controllers;

use App\Models\BookingHistory;
use App\Models\Category;
use App\Models\ItemBookingHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public $MAX_DATA = 10;
    public function StatusCount()
    {
        // $user = auth('sanctum')->user();
        $user = User::find(1); // temp for test admin

        if (true) {
            $booking_status = BookingHistory::whereIn('status', ['pending', 'packed', 'inuse', 'overdue', 'completed'])
                ->get()
                ->pluck('status')->countBy();
            $items = [
                ['count' => $booking_status['pending']   ?? 0, 'name' => 'รออนุมัติ', 'status' => 'pending'],
                ['count' => $booking_status['packed']   ?? 0, 'name' => 'รอรับของ', 'status' => 'packed'],
                ['count' => $booking_status['inuse']   ?? 0, 'name' => 'กำลังใช้งาน', 'status' => 'inuse'],
                ['count' => $booking_status['overdue']   ?? 0, 'name' => 'เกินกำหนดคืน', 'status' => 'overdue'],
                ['count' => $booking_status['completed']   ?? 0, 'name' => 'สำเร็จ', 'status' => 'completed'],
            ];
            return response()->json($items, 200);
        }

        if ($user->hasRole('Teacher')) {
            $booking_status = BookingHistory::whereIn('status', ['pending', 'packed', 'inuse', 'overdue', 'completed'])
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhere('teacher', 'like', $user->name);
                })
                ->limit($this->MAX_DATA)
                ->get()
                ->pluck('status')->countBy();
            $items = [
                ['count' => $booking_status['pending']   ?? 0, 'name' => 'รออนุมัติ'],
                ['count' => $booking_status['packed']   ?? 0, 'name' => 'รอรับของ'],
                ['count' => $booking_status['inuse']   ?? 0, 'name' => 'กำลังใช้งาน'],
                ['count' => $booking_status['overdue']   ?? 0, 'name' => 'เกินกำหนดคืน'],
                ['count' => $booking_status['completed']   ?? 0, 'name' => 'สำเร็จ'],
            ];
            return response()->json($items, 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'No have permission'
            ], 403);
        }
    }

    public function MostProductBooking()
    {
        //ตารางแสดงวัสดุ ยืมบ่อยที่สุด
        // $products = Product::with('categories', 'type')
        $products = Product::withCount('itemBookingHistories')
            ->orderByDesc('item_booking_histories_count')
            ->limit($this->MAX_DATA)
            ->get();
        // ->pluck('name', 'item_booking_histories_count');

        $data_json = [
            'label' => [],
            'data' => []
        ];

        foreach ($products as $value) {
            if ($value->item_booking_histories_count > 0) {
                $data_json['label'][] = $value->name;
                $data_json['data'][] = $value->item_booking_histories_count;
            }
        }

        return response()->json($data_json, 200);
    }

    public function MostCategoryBooking()
    {
        //ตารางแสดงหมวดหมู่ ยืมบ่อยที่สุด
        $categories = ItemBookingHistory::select('product_category')
            ->limit($this->MAX_DATA)
            ->get();
        $allCategories = collect();

        foreach ($categories as $cate) {
            $allCategories = $allCategories->merge(json_decode($cate->product_category, true));
        }

        $result = $allCategories->countBy()->sortDesc();
        $data = [];
        foreach ($result as $name => $item) {
            $data['label'][] = $name;
            $data['data'][] = $item;
        }

        return response()->json($data, 200);
    }

    public function LessOrOutOfStock()
    {
        //ตารางแสดงวัสดุที่ใกล้หมดคลัง + หมดคลัง
        $products = Product::orderBy('stock')->limit($this->MAX_DATA)->get();
        $data = [];
        foreach ($products as $item) {
            if ($item->stock <= 10) {
                $data['label'][] = $item->name;
                $data['data'][] = $item->stock;
            }
        }
        return response()->json($data, 200);
    }

    public function ProductLost()
    {
        $item_booking = ItemBookingHistory::with('bookingHistory')
            ->whereRelation('bookingHistory', 'status', 'completed')
            ->where('product_type', 'ยืมคืน')
            ->where('product_quantity_return', '<=', '1')
            ->limit($this->MAX_DATA)
            ->get();
        $data = [];
        foreach ($item_booking as $item) {
            $data[] = [
                "product_name" => $item->product_name,
                "user_name" => $item->bookingHistory->user_name,
                "booking_number" => $item->bookingHistory->booking_number,
                "booking_at" => $item->created_at,
            ];
        }
        return response()->json($data, 200);
    }

    public function ProductInuse()
    {
        //ตารางแสดงของที่กำลังถูกใช้งาน (ยืม) เรียงจากมากไปน้อย
        $booking = ItemBookingHistory::with('bookingHistory')
            ->whereRelation('bookingHistory', 'status', 'inuse')
            ->where('product_type', 'ยืมคืน')
            ->where('product_quantity_return', '<=', '1')
            ->limit($this->MAX_DATA)
            ->get();
        $data = [];
        foreach ($booking as $item) {
            $data[] = [
                "product_name" => $item->product_name,
                "user_name" => $item->bookingHistory->user_name,
                "booking_number" => $item->bookingHistory->booking_number,
                "booking_at" => $item->created_at,
            ];
        }
        return response()->json($data, 200);
    }

    public function BookingWithStatusNow()
    {
        //ตารางรายการคำขอล่าสุด พร้อมสถานะ
        $booking = BookingHistory::orderByDesc('created_at')->limit($this->MAX_DATA)->get();
        return response()->json($booking, 200);
    }

    public function BookingOverDue()
    {
        // ตารางแสดงรายการเกินกำหนดส่งคืน ชื่อ น.ศ. พร้อมเบอร์โทรติดต่อ
        $booking = BookingHistory::where('status', 'overdue')->limit($this->MAX_DATA)->get();
        return response()->json($booking, 200);
    }

    public function TeacherApproveReject()
    {
        $user_teacher = User::role('Teacher')->pluck('name');

        // ดึงข้อมูลการจอง
        $booking_histories = BookingHistory::select('teacher', 'status')
            ->whereIn('teacher', $user_teacher)
            ->limit($this->MAX_DATA)
            ->get();

        $grouped = $booking_histories
            ->groupBy('teacher')
            ->map(function ($rows) {
                $reject = $rows->where('status', 'reject')->count();
                $approve = $rows->where('status', '!=', 'reject')->count();
                return [
                    'reject' => $reject,
                    'approve' => $approve,
                ];
            });

        $data = [
            'label' => $grouped->keys()->values(),
            'data_reject' => $grouped->pluck('reject')->values(),
            'data_approve' => $grouped->pluck('approve')->values(),
        ];

        return response()->json($data, 200);
    }
}
