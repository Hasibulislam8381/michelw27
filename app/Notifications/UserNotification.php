<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $message;
    protected $data;

    public function __construct($title, $message, $data = [])
    {
        $this->title   = $title;
        $this->message = $message;
        $this->data    = $data;
    }

    public function via($notifiable)
    {
        return ['database'];
    }
    public function toDatabase($notifiable)
    {
        return [
            'title'   => $this->title,
            'message' => $this->message,
            'data'    => $this->data,
        ];
    }
}
