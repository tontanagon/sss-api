<?php

namespace App\Console\Commands;

use App\Jobs\SendNotification;
use App\Models\BookingHistory;
use Illuminate\Console\Command;

class SendNotificationOverdueWeek extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-notification-overdue-week';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send notification for student and teacher if status is overdue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ////////ไม่ได้ใช้
        $date_now = \Carbon\Carbon::now()->startOfDay();
        $bookings = BookingHistory::where('status', 'overdue')->get();
        foreach ($bookings as $booking) {
            $return_date = \Carbon\Carbon::parse($booking->return_at)->addDays(7);
            $diff_in_days = $date_now->diffInDays($return_date, false);
            if ($diff_in_days === 0) {
                $noti = [
                    'booking_id' => $booking->id,
                    'title' => 'แจ้งเตือนการคืนวัสดุ',
                    'style_text' => 'text-[#DD0000]',
                    'message' => "วัสดุที่ยืมครบกำหนดคืนเกิน 1 สัปดาห์ ระบบจะเริ่มคิดค่าปรับ",
                    'url' => "/booking-history/{$booking->id}",
                    'activity' => "กิจกรรม : {$booking->activity_name}",
                ];
                SendNotification::dispatch($booking->user, $noti);
            }
            if ($diff_in_days === 0) {
                $noti = [
                    'booking_id' => $booking->id,
                    'title' => 'แจ้งเตือนการคืนวัสดุ',
                    'style_text' => 'text-[#DD0000]',
                    'message' => "วัสดุที่ยืมครบกำหนดคืนเกิน 1 สัปดาห์ ระบบจะเริ่มคิดค่าปรับ",
                    'url' => "/booking-history/{$booking->id}",
                    'activity' => "กิจกรรม : {$booking->activity_name}",
                ];
                SendNotification::dispatch($booking->user, $noti);
            }
        }
    }
}
