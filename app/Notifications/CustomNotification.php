<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class CustomNotification extends Notification
{
    use Queueable;

    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    // public function toMail($notifiable)
    // {
    //     return (new MailMessage)
    //                 ->line('You have a new notification.')
    //                 ->action('View Notification', url('/'))
    //                 ->line('Thank you for using our application!');
    // }

    // public function toDatabase($notifiable)
    // {
    //     return new DatabaseMessage([
    //         'message' => $this->data['message'],
    //         'url' => $this->data['url']
    //     ]);
    // }

    // public function toBroadcast($notifiable)
    // {
    //     return new BroadcastMessage([
    //         'message' => $this->data['message'],
    //         'url' => $this->data['url']
    //     ]);
    // }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->data['title'],
            'description' => $this->data['description'],
            'link' => $this->data['link'],
            'image' => $this->data['image'],
            'guard' => $this->data['guard'],
        ];
    }
}
