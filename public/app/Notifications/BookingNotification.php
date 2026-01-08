<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingNotification extends Notification
{
    use Queueable;

    public $data;
    /**
     * Create a new notification instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {

        return (new MailMessage)
            ->subject($this->data['subject'])
            ->view('mail.custom_email', [
                'data' => $this->data
            ]);
        // ->markdown('mail.booking', ['data' => $this->data]);
        // ->greeting('สวัสดี')
        // ->subject($this->data['title'])
        // ->line($this->data['message'])
        // ->when(!empty($this->data['remark']), function ($mail) {
        //     $mail->line($this->data['remark']);
        // })
        // ->line($this->data['activity'])
        // ->action('ดูรายละเอียด', 'http://localhost:5189' . $this->data['url'])
        // ->line('ตรวจสอบรายละเอียดได้ที่ Smart Store System');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            $this->data
        ];
    }
}
