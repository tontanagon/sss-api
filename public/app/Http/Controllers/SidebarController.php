<?php

namespace App\Http\Controllers;

use App\Models\BookingHistory;

class SideBarController extends Controller
{
    public function mapDataStatus($booking_status)
    {
        // $items = [
        //     ['count' => $booking_status['pending']   ?? 0, 'name' => 'รออนุมัติ', 'status' => 'pending'],
        //     ['count' => $booking_status['approved']   ?? 0, 'name' => 'รออนุมัติ', 'status' => 'approved'],
        //     ['count' => $booking_status['packed']   ?? 0, 'name' => 'รอรับของ', 'status' => 'packed'],
        //     ['count' => $booking_status['inuse']   ?? 0, 'name' => 'กำลังใช้งาน', 'status' => 'inuse'],
        //     ['count' => $booking_status['returned']   ?? 0, 'name' => 'กำลังใช้งาน', 'status' => 'returned'],
        //     ['count' => $booking_status['overdue']   ?? 0, 'name' => 'เกินกำหนดคืน', 'status' => 'overdue'],
        //     ['count' => $booking_status['completed']   ?? 0, 'name' => 'สำเร็จ', 'status' => 'completed'],
        //     ['count' => $booking_status['incomplete']   ?? 0, 'name' => 'สำเร็จ', 'status' => 'incomplete'],
        //     ['count' => $booking_status['reject']   ?? 0, 'name' => 'สำเร็จ', 'status' => 'reject'],
        // ];
        $items = [
            'pending' => ['count' => $booking_status['pending']   ?? 0, 'name' => 'รออนุมัติ'],
            'approved' => ['count' => $booking_status['approved']   ?? 0, 'name' => 'รองจัดของ'],
            'packed' => ['count' => $booking_status['packed']   ?? 0, 'name' => 'รอรับของ'],
            // 'inuse' => ['count' => $booking_status['inuse']   ?? 0, 'name' => 'กำลังใช้งาน'],
            'returned' => ['count' => $booking_status['returned']   ?? 0, 'name' => 'กำลังใช้งาน'],
            'overdue' => ['count' => $booking_status['overdue']   ?? 0, 'name' => 'เกินกำหนดคืน'],
            // 'completed' => ['count' => $booking_status['completed']   ?? 0, 'name' => 'สำเร็จ'],
            'incomplete' => ['count' => $booking_status['incomplete']   ?? 0, 'name' => 'ของไม่ครบ'],
            // 'reject' => ['count' => $booking_status['reject']   ?? 0, 'name' => 'สำเร็จ'],
        ];
        return $items;
    }

    public function BookingStatusCountAdmin()
    {
        $booking_status = BookingHistory::get()
            ->pluck('status')->countBy();

        $booking_count = $this->mapDataStatus($booking_status);

        return response()->json($booking_count, 200);
    }

    public function BookingStatusCountTeacher()
    {
        $user = auth('sanctum')->user();
        $booking_status = BookingHistory::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhere('teacher', 'like', $user->name);
        })
            ->get()
            ->pluck('status')->countBy();

        $booking_count = $this->mapDataStatus($booking_status);

        return response()->json([
            'status' => true,
            'message' => 'Get booking count success.',
            'data' => $booking_count
        ], 200);
    }
}
