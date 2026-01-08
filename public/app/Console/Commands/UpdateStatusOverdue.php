<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class UpdateStatusOverdue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-status-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update bookingHistory inuse to overdue status if overdue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date_now = \Carbon\Carbon::now()->startOfDay();
        $bookings = \App\Models\BookingHistory::get();
        DB::beginTransaction();
        try {
            foreach ($bookings as $booking) {
                if ($booking->return_at > $date_now && in_array($booking->status,['inuse' ,'packed'])) {
                    $booking->status = 'overdue';
                    $booking->save();
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            dd($e);
        }
    }

    // public function changeStatus()
    // {
    //     $date_now = \Carbon\Carbon::now();
    //     $bookings = \App\Models\BookingHistory::get();

    //     DB::beginTransaction();
    //     try {
    //         foreach ($bookings as $booking) {
    //             if ($booking->return_at < $date_now && $booking->status == 'inuse') {
    //                 $booking->status = 'overdue';
    //                 $booking->save();
    //             }
    //         }
    //         DB::commit();
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         dd($e);
    //     }
    // }
}
