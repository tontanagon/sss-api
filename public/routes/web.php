<?php

use App\Http\Controllers\ProductController;
use App\Jobs\SendNotification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    return view('test');
});


Route::get('/email', [ProductController::class, 'testEmail']);

// Route::get('/email', function () {
//     return view('mail.custom_email');
// });
// Route::get('/test-email', function () {
//     try {
//         Mail::raw('This is a test email from Laravel 12 SMTP setup.', function ($message) {
//             $message->to('your_email@example.com')
//                 ->subject('SMTP Test Email');
//         });

//         return '✅ Test email has been sent successfully!';
//     } catch (\Exception $e) {
//         return '❌ Error sending email: ' . $e->getMessage();
//     }
// });

Route::get('/test-email', function () {
    try {
        $user = \App\Models\User::find(7);
        $booking = \App\Models\BookingHistory::with('subjectName')->where('id', '104')->first();
        $data = [
            'title' => 'แจ้งเตือนการจอง',
            'style_text' => 'text-[#4CAF50]',
            'message' => "รายการจองมีการเปลี่ยนแปลง กรุณาตรวจสอบก่อนรับของ",
            'url' => "/booking-history/{$booking->id}",
            'booking_data' => $booking,
        ];
        SendNotification::dispatch($user, $data);

        return '✅ Test email has been sent successfully!';
    } catch (\Exception $e) {
        return '❌ Error sending email: ' . $e->getMessage();
    }
})->middleware('throttle:' . config('sss.throttle_limit'));
