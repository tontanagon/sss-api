<?php

namespace App\Console\Commands;

use App\Jobs\SendNotification;
use App\Models\BookingHistory;
use Illuminate\Console\Command;

class SendNotificationBeforeOverdue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-notification-before-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //ไม่ได้ใช้
        $date_now = \Carbon\Carbon::now();
        $bookings = BookingHistory::where('status', 'inuse')->get();

        foreach ($bookings as $booking) {
            $return_date = \Carbon\Carbon::parse($booking->return_at);
            $diff_in_days = $date_now->diffInDays($return_date, false);

            if ($diff_in_days === 1) {
                $noti = [
                    'booking_id' => $booking->id,
                    'title' => 'แจ้งเตือนการคืนวัสดุ',
                    'style_text' => 'text-[#DD0000]',
                    'message' => "วัสดุที่คุณยืมใกล้ครบกำหนดคืน กรุณาคืนวัสดุภายในวันที่ {$return_date->format('d/m/Y')}",
                    'url' => "/booking-history/{$booking->id}",
                    'activity' => "กิจกรรม : {$booking->activity_name}",
                ];
                SendNotification::dispatch($booking->user, $noti);
            }
        }
    }
}
