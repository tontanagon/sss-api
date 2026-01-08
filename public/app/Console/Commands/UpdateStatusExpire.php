<?php

namespace App\Console\Commands;

use App\Models\BookingHistory;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateStatusExpire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-status-expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update bookingHistory pending status if expire';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date_now = \Carbon\Carbon::now();
        $bookings = BookingHistory::with('itemBookingHistories')
            ->whare("created_at", "<",  $date_now)
            ->whare('status', 'pending')
            ->get();

        DB::beginTransaction();
        try {
            foreach ($bookings as $booking) {
                foreach ($booking->itemBookingHistories as $item) {
                    if ($item['product_id']) {
                        $product_by_id = Product::find($item['product_id']);

                        if (!$product_by_id) {
                            continue;
                        }

                        $current_stock = $product_by_id->stock + $item['product_quantity'];

                        $booking->productStockHistory()->create([
                            'product_id' => $item['product_id'],
                            'stock' => $product_by_id->stock,
                            'type' => 'increase',
                            'add_type' => 'booking',
                            'by_user_id' => $booking->user_id,
                            'before_stock' => $product_by_id->stock,
                            'after_stock' => $current_stock,
                        ]);

                        $product_by_id->stock = $current_stock;
                        $product_by_id->save();
                    }
                }

                $booking->update([
                    'status' => 'reject',
                    'remark' => 'หมดอายุการอนุมัติ'
                ]);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            dd($e);
        }
    }
}
