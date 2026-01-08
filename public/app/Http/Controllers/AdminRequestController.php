<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotification;
use App\Models\BookingHistory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminRequestController extends Controller
{
    private function changeQuantityBooking($request, $booking, $user)
    {
        $items_change = collect($request->items_change);
        DB::beginTransaction();
        try {
            if ($items_change->isNotEmpty()) {
                $check_reject = $items_change->sum('product_quantity');
                if ($check_reject > 0) {
                    foreach ($booking->itemBookingHistories as $index => $item) {
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
                            // จะใช้ product_quantity_return เก็บตัวที่เปลี่ยนแปลงเพื่อไว้เปรียบเทียบ
                            $item->product_quantity = $newQuantity;
                            $item->product_quantity_return = $newQuantity;
                            $item->save();
                        }
                    }
                    if ($request->remark) {
                        $booking->remark = $request->remark;
                    }


                    $booking->save();
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'ไม่สามารถบันทึกได้เนื่องจากมี จำนวนวัสดุ 0 ชิ้น',
                    ], 500);
                }
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error("Booking confirm error: {$e->getMessage()}", ['exception' => $e]);

            return response()->json([
                'status' => false,
                'message' => 'ไม่สามารถบันทึกได้ กรุณาติดต่อผู้ดูแล',
            ], 500);
        }
        return;
    }

    public function requestAll(Request $request)
    {
        $paginate = ($request->limit && $request->limit !== 'null') ? (int)$request->limit : 10;
        $status = ($request->search_status && $request->search_status !== 'null') ? $request->search_status : 'all';
        $search_text = ($request->search_text && $request->search_text !== 'null') ? $request->search_text : '';

        $booking_list = BookingHistory::with('itemBookingHistories')
            ->where(function ($query) use ($search_text) {
                $query->where('user_name', 'like', "%{$search_text}%")
                    ->orWhere('user_code', 'like', "%{$search_text}%")
                    ->orWhere('booking_number', 'like', "%{$search_text}%");
            })
            ->when($status !== 'all', function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy(
                $status === 'all' ? 'updated_at' : 'created_at',
                $status === 'all' ? 'desc' : 'asc'
            );



        // dd($booking_list->paginate($paginate));
        // dd($request->all());->paginate($paginate);
        return response()->json($booking_list->paginate($paginate), 200);
    }

    public function requestById($id)
    {
        $booking_list = BookingHistory::with([
            'itemBookingHistories.product',
            'bookingStatusHistories' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }
        ])->find($id);
        if (!$booking_list) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found',
            ], 404);
        }
        return response()->json($booking_list, 200);
    }

    public function requestPacked(Request $request, $id)
    {
        $user = auth('sanctum')->user();
        $booking = BookingHistory::with('itemBookingHistories.product')->find($id);

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $booking->update(['status' => 'packed']);

            $this->changeQuantityBooking($request, $booking, $user);

            /////แจ้งเตือน @นศ จัดของเสร็จสิ้น
            $date_return = \Carbon\Carbon::parse($booking->return_at)->format('d/m/Y');
            $noti = [
                'subject' => "แจ้งเตือนการยืม #{$booking->booking_number} วัสดุถูกจัดเตรียมเรียบร้อย",
                'display_button' => 'ดูรายการวัสดุ',
                'title' => 'กรุณามารับวัสดุที่คุณจอง',
                'style_text' => 'text-[#9C27B0]',
                'message' => "วัสดุอุปกรณ์ของคุณถูกจัดเตรียมเรียบร้อย กรุณามารับก่อน {$date_return}",
                'url' => "/booking-history/{$booking->id}",
                'booking_data' => $booking,

            ];
            SendNotification::dispatch($booking->user, $noti);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error("Booking confirm error: {$e->getMessage()}", ['exception' => $e]);

            return response()->json([
                'status' => false,
                'message' => 'ระบบขัดข้อง กรุณาติดต่อผู้ดูแล',
            ], 500);
        }

        if ($request->isConfirmList ?? false) {
            return true;
        }

        return response()->json([
            'status' => true,
            'message' => 'Pack successful'
        ], 200);
    }

    public function requestConfirm(Request $request, $id)
    {
        $booking = BookingHistory::with('itemBookingHistories.product')->find($id);
        $items_change = collect($request->items_change ?? []);

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        // if (!in_array($booking->status, ['returned'])) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => "Booking can't confirm",
        //     ], 400);
        // }

        DB::beginTransaction();
        try {
            foreach ($booking->itemBookingHistories as $item) {
                $product = $item->product;
                $current_stock = $product->stock + $item->product_quantity_return;
                if (!$product) continue;
                if ($items_change->isNotEmpty()) {
                    $changed = $items_change->firstWhere('id', $item->id);
                    if ($changed) {
                        $current_stock = $product->stock + $changed['product_quantity_return'];
                    }
                }
                $booking->productStockHistory()->create([
                    'product_id' => $item->product_id,
                    'stock' => $product->stock,
                    'type' => 'increase',
                    'add_type' => 'booking',
                    'by_user_id' => $booking->user_id,
                    'before_stock' => $product->stock,
                    'after_stock' => $current_stock,
                ]);

                $product->update(['stock' => $current_stock]);
            }
            $booking->update(['status' => 'completed', 'remark' => $request->remark ?? null]);

            /////แจ้งเตือน @นศ คืนของเสร็จสิ้น
            $noti = [
                'subject' => "แจ้งเตือนการคืน #{$booking->booking_number} วัสดุคืนเรียบร้อย",
                'display_button' => 'ดูประวัติการคืน',
                'title' => 'ระบบได้บันทึกการคืนวัสดุเรียบร้อยแล้ว',
                'style_text' => 'text-[#2E7D32]',
                'message' => "รายการจองหมายเลข {$booking->booking_number} ถูกตรวจสอบแล้ว ถูกต้องครบถ้วน",
                'url' => "/booking-history/{$booking->id}",
                'booking_data' => $booking,
            ];
            SendNotification::dispatch($booking->user, $noti);


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
            'message' => 'Return successful'
        ], 200);
    }

    public function requestIncomplete(Request $request, $id)
    {
        $booking = BookingHistory::with('itemBookingHistories')->find($id);
        $items_change = collect($request->items_change ?? []);

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $booking->update(['status' => 'incomplete', 'remark' => $request->remark ?? null]);

            if ($items_change->isNotEmpty()) {
                foreach ($booking->itemBookingHistories as $item) {
                    $changed = $items_change->firstWhere('id', $item->id);
                    if ($changed) {
                        $item->update([
                            'product_quantity_return' => $changed['product_quantity_return'],
                        ]);
                    }
                }
            }
            ////แจ้งเตือน @นศ คืนไม่ครบ
            $noti = [
                'subject' => "แจ้งเตือนการคืน #{$booking->booking_number} การคืนวัสดุไม่ครบถ้วน",
                'display_button' => 'ตรวจสอบรายการวัสดุที่เหลือ',
                'title' => 'กรุณาตรวจสอบรายการวัสดุที่ยังไม่ได้คืน',
                'style_text' => 'text-[#DD0000]',
                'message' => "รายการจองหมายเลข {$booking->booking_number} มีการคืนวัสดุไม่ครบถ้วน กรุณาตรวจสอบรายการวัสดุที่ยังไม่ได้คืน",
                'url' => "/booking-history/{$booking->id}",
                'booking_data' => $booking,
            ];
            SendNotification::dispatch($booking->user, $noti);

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
            'message' => 'Return successful'
        ], 200);
    }

    public function requestSaveChange(Request $request, $id)
    {
        $user = auth('sanctum')->user();
        $booking = BookingHistory::with('itemBookingHistories')->find($id);

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        $this->changeQuantityBooking($request, $booking, $user);

        // ////เตือนแจ้ง @นศ มีการเปลี่ยนแปลงจากadmin
        // $noti = [
        //     'subject' => '',
        //     'display_button' => '',
        //     'title' => 'แจ้งเตือนการจอง',
        //     'style_text' => 'text-[#4CAF50]',
        //     'message' => "รายการจองของคุณมีการเปลี่ยนแปลงโดย {$user->name} กรุณาตรวจสอบก่อนรับของ",
        //     'url' => "/booking-history/{$booking->id}",
        //     'booking_data' => $booking,
        // ];
        // SendNotification::dispatch($booking->user, $noti);

        return response()->json([
            'status' => false,
            'message' => 'Save booking successful'
        ], 200);
    }
    public function requestConfirmPackList(Request $request)
    {
        $array_id = $request->id_check ?? [];

        if (count($array_id) === 0) {
            return response()->json([
                'status' => true,
                'message' => 'กรุณาเลือกรายการก่อนยืนยัน'
            ], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($array_id as $key => $value) {
                $this->requestPacked($request, $value);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error("Booking confirm error: {$e->getMessage()}", ['exception' => $e]);

            return response()->json([
                'status' => false,
                'message' => 'ไม่สามารถยืนยันหลายรายการได้ กรุณาติดต่อผู้ดูแล',
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Confirm successful'
        ], 200);
    }

    public function PreviewPrint(Request $request)
    {
        $id_check = $request->id;
        if ($id_check) {
            $id_check = explode(',', $id_check);
            $booking = BookingHistory::with('itemBookingHistories')->whereIn('id', $id_check)->get();
        }

        // dd($booking);

        return response()->json($booking ?? [], 200);
    }

    public function extendDate(Request $request)
    {
        $id = $request->id;
        $dateExtend = $request->dateExtend;

        try {
            $booking = BookingHistory::find($id);

            $booking->update(['return_at' => $dateExtend]);
        } catch (\Throwable $th) {
            \Log::error("Booking confirm error: {$th->getMessage()}", ['exception' => $th]);
            return response()->json([
                'status' => false,
                'message' => "Can't extend date"
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Extend date success'
        ], 200);
    }

    public function changeStatus(Request $request)
    {
        $booking = BookingHistory::find($request->id);
        if (!in_array($booking->status, ['reject', 'completed'])) {
            return match ($request->change_status_to) {
                'pending'    => $this->changeStatusToPending($request),
                'approved'   => app(ApproveController::class)->approve($request),
                'packed'     => $this->requestPacked($request, $request->id),
                'inuse'      => app(BookingHistoryController::class)->confirmPickup($request, $request->id),
                'returned'   => app(BookingHistoryController::class)->confirmReturn($request),
                'completed'  => $this->requestConfirm($request, $request->id),
                'incomplete' => $this->requestIncomplete($request, $request->id),
                'overdue'    => $this->changeStatusToOverdue($request),
                'reject'     => app(ApproveController::class)->reject($request),
                default      => response()->json([
                    'status'  => false,
                    'message' => 'Invalid status change'
                ], 422),
            };
        }

        return response()->json([
            'status' => false,
            'message' => 'ไม่สามารถเปลี่ยนสถานะของการจองนี้ได้'
        ], 400);
    }

    public function changeStatusToPending($request)
    {
        $booking = BookingHistory::with('itemBookingHistories')->find($request->id);

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            foreach ($booking->itemBookingHistories as $items) {
                $items->update(['status' => 'pending']);
            }

            $booking->update(['status' => 'pending']);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => true,
                'message' => 'Error'
            ], 500);
        }


        return response()->json([
            'status' => true,
            'message' => 'Change status successful'
        ], 200);
    }

    public function changeStatusToOverdue($request)
    {
        $booking = BookingHistory::with('itemBookingHistories')->find($request->id);

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        $booking->update(['status' => 'overdue']);

        return response()->json([
            'status' => true,
            'message' => 'Change status successful'
        ], 200);
    }
}
