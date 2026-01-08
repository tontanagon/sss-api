<?php

namespace App\Console\Commands;

use App\Jobs\SendNotification;
use App\Models\BookingHistory;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendNotificationOverdue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-notification-overdue';

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
        $date_now = \Carbon\Carbon::now()->startOfDay();
        $bookings = BookingHistory::with('user', 'itemBookingHistories.product')->whereIn('status', ['inuse', 'overdue'])->get();

        foreach ($bookings as $booking) {
            $return_date = \Carbon\Carbon::parse($booking->return_at);
            $diff_in_days = $date_now->diffInDays($return_date, false);
            if ($diff_in_days == -1) {
                $this->beforeOverDue($booking, $return_date);
            }
            if ($diff_in_days == 1) {
                $this->OverDue($booking);
            }
            if ($diff_in_days == 7) {
                $this->OverDueWeek($booking);
            }
        }
    }

    public function beforeOverDue($booking, $return_date)
    {
        ////แจ้งเตือน @นศ ส่งก่อนครบกำหนด 1 วัน
        $noti = [
            'subject' => "แจ้งเตือนการคืน #{$booking->booking_number} ใกล้ครบกำหนด",
            'title' => 'กรุณานำวัสดุมาคืนก่อนครบกำหนด',
            'style_text' => 'text-[#DD0000]',
            'message' => "วัสดุที่คุณยืมใกล้ครบกำหนดคืน กรุณาคืนวัสดุภายในวันที่ {$return_date->thaidate('j F Y')}",
            'url' => "/booking-history/{$booking->id}",
            'display_button' => 'ดูรายละเอียดการจอง',
            'booking_data' => $booking,
        ];
        SendNotification::dispatch($booking->user, $noti);
    }

    public function OverDue($booking)
    {
        //ส่งหาอาจารมั้ย
        ////แจ้งเตือน @นศ ส่งหลังครบกำหนด 1 วัน
        $noti = [
            'subject' => "แจ้งเตือนการคืน #{$booking->booking_number} การคืนวัสดุล่าช้า",
            'display_button' => 'ตรวจสอบสถานะ',
            'title' => 'กรุณานำวัสดุมาคืนโดยด่วน',
            'style_text' => 'text-[#DD0000]',
            'message' => "วัสดุที่คุณยืมเกินกำหนดคืน กรุณาคืนวัสดุโดยเร็วที่สุด",
            'url' => "/booking-history/{$booking->id}",
            'booking_data' => $booking,
        ];
        SendNotification::dispatch($booking->user, $noti);
    }

    public function OverDueWeek($booking)
    {
        ////แจ้งเตือน @นศ ส่งหลังครบกำหนด 7 วัน
        $noti = [
            'subject' => "แจ้งเตือนการคืน #{$booking->booking_number} คืนวัสดุเกินกำหนด 7 วัน",
            'display_button' => 'ตรวจสอบสถานะ',
            'title' => 'กรุณาติดต่อเจ้าหน้าที่เพื่อดำเนินการคิดค่าปรับและคืนวัสดุ',
            'style_text' => 'text-[#DD0000]',
            'message' => "วัสดุที่ยืมครบกำหนดคืน 7 วันแล้ว กรุณาติดต่อเจ้าหน้าที่เพื่อดำเนินการคิดค่าปรับและคืนวัสดุโดยด่วน",
            'url' => "/booking-history/{$booking->id}",
            'booking_data' => $booking,
        ];
        SendNotification::dispatch($booking->user, $noti);

        ////แจ้งเตือน @อาจารย์ ส่งหลังครบกำหนด 7 วัน
        $teacher = User::where('name', $booking->teacher)->first();
        $noti_teacher = [
            'subject' => "แจ้งเตือนการคืน #{$booking->booking_number} รายการจองของนักศึกษาเกินกำหนด 7 วัน",
            'display_button' => 'ตรวจสอบสถานะ',
            'title' => 'นักศึกษาไม่คืนวัสดุเกินกำหนด 7 วัน',
            'style_text' => 'text-[#DD0000]',
            'message' => "นักศึกษายืมวัสดุครบกำหนดคืน 7 วัน กรุณาติดต่อนักศึกษาเพื่อคิดค่าปรับและคืนวัสดุ",
            'url' => "/medtch/{$booking->id}",
            'booking_data' => $booking,
        ];

        SendNotification::dispatch($teacher, $noti_teacher);

        ////แจ้งเตือน @admin ส่งหลังครบกำหนด 7 วัน
        $admins = \App\Models\User::role('Administrator')->get();
        $noti_admin = [
            'subject' => "แจ้งเตือนการคืน #{$booking->booking_number} รายการจองของนักศึกษาเกินกำหนด 7 วัน",
            'display_button' => 'ตรวจสอบรายการ',
            'title' => 'นักศึกษาไม่คืนวัสดุเกินกำหนด 7 วัน',
            'style_text' => 'text-[#DD0000]',
            'message' => "นักศึกษายืมวัสดุครบกำหนดคืนเกิน 7 วัน กรุณาติดต่อนักศึกษาเพื่อดำเนินการคิดค่าปรับและตรวจรับวัสดุโดยด่วน",
            'url' => "/medadm/requests/{$booking->id}",
            'booking_data' => $booking,
        ];

        foreach ($admins as $admin) {
            SendNotification::dispatch($admin, $noti_admin);
        }
    }
}
