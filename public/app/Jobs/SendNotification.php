<?php

namespace App\Jobs;

use App\Notifications\BookingNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendNotification implements ShouldQueue
{
    use Queueable;

    public $user;
    public $noti;

    /**
     * Create a new job instance.
     */
    public function __construct($user, $noti)
    {
        $this->user = $user;
        $this->noti = $noti;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->user->notify(new BookingNotification($this->noti));
    }
}
