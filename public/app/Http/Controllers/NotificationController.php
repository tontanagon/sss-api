<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function getNotificationUnread(Request $request)
    {
        $paginate = 10;
        if (isset($request->limit)) {
            $paginate = $request->limit;
        }

        $user = auth('sanctum')->user();
        $noti = $user->unreadNotifications()->paginate($paginate);

        if ($noti->isEmpty()) {
            return response()->json([], 200);
        }

        return response()->json($noti, 200);
    }

    public function getNotificationReaded(Request $request)
    {
        $paginate = 10;
        if (isset($request->limit)) {
            $paginate = $request->limit;
        }

        $user = auth('sanctum')->user();
        $noti = $user->readNotifications()->paginate($paginate);

        if ($noti->isEmpty()) {
            return response()->json([], 200);
        }

        return response()->json($noti, 200);
    }

    public function makeAsRead(Request $request)
    {
        $user = auth('sanctum')->user();
        // หา noti ที่ตรงกับ id
        if ($request->uuid === 'all') {
            $noti = $user->unreadNotifications()->get();
        }else {
            $noti = $user->unreadNotifications()->where('id', $request->uuid)->get();
        }

        if (!$noti) {
            return response()->json([
                'status' => false,
                'message' => 'ไม่พบการแจ้งเตือนนี้หรือถูกอ่านแล้ว'
            ], 200);
        }

        // mark as read
        $noti->markAsRead();

        return response()->json([
            'status' => true,
            'message' => 'อัปเดตสถานะอ่านเรียบร้อย',
            'data' => $noti
        ], 200);
    }
}
