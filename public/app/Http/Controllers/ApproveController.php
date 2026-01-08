<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotification;
use App\Models\BookingHistory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApproveController extends Controller
{
    public function approve(Request $request)
    {
        $user = auth('sanctum')->user();
        $booking = BookingHistory::with('user', 'itemBookingHistories')->where('id', $request->id)->first();
        $items_change = collect($request->items_change);

        ////แจ้งเตือน @นศ ได้รับการอนุมัติ
        $noti = [
            'subject' => "แจ้งเตือนการจอง #{$booking->booking_number} รายการจองได้รับการอนุมัติ",
            'display_button' => 'ดูสถานะการจอง',
            'title' => 'กรุณารอเจ้าหน้าที่จัดเตรียมวัสดุ',
            'style_text' => 'text-[#4CAF50]',
            'message' => "รายการจองของคุณหมายเลข {$booking->booking_number} ได้รับการอนุมัติเรียบร้อยแล้ว โปรดรอเจ้าหน้าที่ดำเนินการจัดเตรียมของ",
            'url' => "/booking-history/{$booking->id}",
            'booking_data' => $booking,
        ];

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        // if ($booking->status !== 'pending') {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Booking is not in a pending state',
        //     ], 400);
        // }



        DB::beginTransaction();
        try {
            $items = $booking->itemBookingHistories()->with('product')->get();
            if ($items_change->isNotEmpty()) {
                $check_reject = collect($items_change)->sum('product_quantity');
                if ($check_reject > 0) {
                    foreach ($items as $index => $item) {
                        if (!$item->product) {
                            continue;
                        }
                        $oldQuantity = $item->product_quantity;
                        $newQuantity = $items_change[$index]['product_quantity'];

                        if ($oldQuantity != $newQuantity) {
                            $stockChange = $newQuantity - $oldQuantity;

                            $beforeStock = $item->product->stock;

                            if ($stockChange > 0 && $beforeStock < $stockChange) {
                                return response()->json([
                                    'status'  => false,
                                    'message' => 'จำนวนวัสดุ-อุปกรณ์ไม่เพียงพอ ' . $item->product->name .
                                        ' คงเหลือ ' . $beforeStock
                                ], 422);
                            }

                            $afterStock = $beforeStock - $stockChange;
                            $item->product->stock = $afterStock;
                            $item->product->save();

                            $item->product->productStockHistories()->create([
                                'stock'             => $beforeStock,
                                'type'              => $stockChange > 0 ? 'decrease' : 'increase',
                                'add_type'          => 'booking',
                                'by_user_id'        => $user->id,
                                'booking_history_id' => $booking->id,
                                'before_stock'      => $beforeStock,
                                'after_stock'       => $afterStock,
                                'remark'            => 'Change product stock by ' . $user->name,
                            ]);

                            $item->product_quantity = $newQuantity;
                            $item->product_quantity_return = $newQuantity;
                            $item->save();
 
                            ////แจ้งเตือน @นศ มีการเปลี่ยนแปลงจากอาจาร
                            $noti = [
                                'subject' => "แจ้งเตือนการจอง #{$booking->booking_number} การเปลี่ยนแปลงจากอาจารย์",
                                'display_button' => 'ดูรายละเอียดที่เปลี่ยนแปลง',
                                'title' => 'กรุณาตรวจสอบข้อมูลการจองล่าสุด',
                                'style_text' => 'text-[#4CAF50]',
                                'message' => "รายการจองของคุณมีการเปลี่ยนแปลงโดย {$user->name} กรุณาตรวจสอบก่อนรับของ",
                                'url' => "/booking-history/{$booking->id}",
                                'booking_data' => $booking,
                            ];
                        }
                    }
                    if ($request->remark) {
                        $booking->remark = $request->remark;
                    }
                    $booking->status = 'approved';
                    $booking->save();

                    SendNotification::dispatch($booking->user, $noti);
                } else {
                    return $this->reject($request);
                }
            } else {
                $booking->status = 'approved';
                $booking->save();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'ระบบขัดข้อง กรุณาติดต่อผู้ดูแล ' . $e,
            ], 500);
        }

        $admins = \App\Models\User::role('Administrator')->get();
        ///แจ้งเตือน @admin ตอนที่อาจารย์อนุมัติ

        $noti = [
            'subject' => "แจ้งเตือนการจอง #{$booking->booking_number} อาจารย์อนุมัติรายการจอง",
            'display_button' => 'จัดการรายการนี้',
            'title' => 'รายการจองได้รับการอนุมัติ',
            'style_text' => 'text-[#FCB500]',
            'message' => "รายการของ {$booking->user_name} ได้รับการอนุมัติเรียบร้อยแล้ว กรุณาตรวจสอบรายละเอียดและจัดเตรียมของตามคำขอ",
            'url' => "/medadm/requests/{$booking->id}",
            'booking_data' => $booking,
        ];
        foreach ($admins as $admin) {
            SendNotification::dispatch($admin, $noti);
        }

        return response()->json([
            'status' => true,
            'message' => 'Approve successful',
        ], 200);
    }

    public function reject(Request $request)
    {
        $user = auth('sanctum')->user();
        if ($user->hasRole('Administrator')) {
            //for admin change status
            $booking = BookingHistory::with('user', 'itemBookingHistories.product')->where('id', $request->id)->first();
        } else {
            $booking = BookingHistory::with('user', 'itemBookingHistories.product')->where('teacher', $user->name)->where('id', $request->id)->first();
        }

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        // if ($booking->status != 'pending') {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Status not pending',
        //     ], 422);
        // }


        DB::beginTransaction();
        try {
            $stocks = $booking->itemBookingHistories;

            foreach ($stocks as $stock) {
                $product = $stock->product;
                $stock_change = $product->stock + $stock->product_quantity;
                $booking->productStockHistory()->create([
                    'product_id' => $stock->product_id,
                    'stock' => $product->stock,
                    'type' => 'increase',
                    'add_type' => 'booking',
                    'by_user_id' => $user->id,
                    'before_stock' => $product->stock,
                    'after_stock' => $stock_change,
                    'remark' => 'Reject by Teacher'
                ]);

                $product->stock = $stock_change;
                $product->save();
            }

            $booking->status = 'reject';
            if ($request->remark) {
                $booking->remark = $request->remark;
            }
            $booking->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => true,
                'message' => 'ระบบขัดข้อง กรุณาติดต่อผู้ดูแล',
            ], 500);
        }

        ////แจ้งเตือน @นศ ตอนที่ถูกยกเลิก
        $noti = [
            'subject' => "แจ้งเตือนการจอง #{$booking->booking_number} รายการจองถูกยกเลิก",
            'display_button' => 'ดูเหตุผลการยกเลิก',
            'title' => 'กรุณาตรวจสอบสาเหตุการยกเลิก',
            'style_text' => 'text-gray-500',
            'message' => "รายการจองของคุณหมายเลข {$booking->booking_number} ถูกยกเลิก กรุณาตรวจสอบรายละเอียด",
            'remark' => $booking->remark,
            'url' => "/booking-history/{$booking->id}",
            'booking_data' => $booking,
        ];
        SendNotification::dispatch($booking->user, $noti);

        return response()->json([
            'status' => true,
            'message' => 'ยกเลิกการจองสำเร็จ',
        ], 200);
    }


    public function approveListPending(Request $request)
    {
        $user = auth('sanctum')->user();
        $paginate = 10;
        $search_text = '';
        if (isset($request->limit)) {
            $paginate = $request->limit;
        }

        if (isset($request->search_text)) {
            $search_text = $request->search_text;
        }

        $booking_list = BookingHistory::with('itemBookingHistories')
            ->where('status', 'pending')
            ->where('teacher', $user->name)
            ->where(function ($query) use ($search_text) {
                $query->where('user_name', 'like', "%{$search_text}%")
                    ->orWhere('user_code', 'like', "%{$search_text}%")
                    ->orWhere('booking_number', 'like', "%{$search_text}%");
            })

            ->orderBy('created_at', 'asc')
            ->paginate($paginate);

        return response()->json($booking_list, 200);
    }

    public function approveListApproved(Request $request)
    {
        $user = auth('sanctum')->user();
        $paginate = 10;
        $search_text = '';
        if (isset($request->limit)) {
            $paginate = $request->limit;
        }

        if (isset($request->search_text)) {
            $search_text = $request->search_text;
        }

        $booking_list = BookingHistory::with('itemBookingHistories')
            ->whereNot('status', 'pending')
            ->where('teacher', $user->name)
            ->where(function ($query) use ($search_text) {
                $query->where('user_name', 'like', "%{$search_text}%")
                    ->orWhere('booking_number', 'like', "%{$search_text}%")
                    ->orWhere('user_code', 'like', "%{$search_text}%");
            })

            ->orderBy('created_at', 'desc')
            ->paginate($paginate);

        return response()->json($booking_list, 200);
    }

    public function checkProductStock(Request $request)
    {
        if (!$request->id) {
            return response()->json([
                'status' => false,
                'message' => 'No have request id'
            ], 422);
        }
        // dd($request->id);
        $product = Product::whereIn('id', $request->id)->get(['id', 'stock']);

        return response()->json($product, 200);
    }

    public function approveById($id)
    {
        $user = auth('sanctum')->user();
        $booking = BookingHistory::with(['itemBookingHistories.product', 'bookingStatusHistories' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }])->where('teacher', $user->name)->find($id);

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        if (!isset($booking)) {
            return response()->json(['status' => true, 'message' => 'Booking not found',], 404);
        }

        return response()->json($booking, 200);
    }
}
