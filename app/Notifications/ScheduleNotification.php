<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\FcmMessage;
use Illuminate\Notifications\Notification;

class ScheduleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $body;

    public function __construct($title, $body)
    {
        $this->title = $title;
        $this->body = $body;
    }

    public function via($notifiable)
    {
        return ['fcm'];
    }

    public function toFcm($notifiable)
    {
        return (new FcmMessage)
            ->setContent([
                'title' => $this->title,
                'body' => $this->body,
            ])
            ->setData([
                'custom_data' => 'value',
            ]);
    }
}
