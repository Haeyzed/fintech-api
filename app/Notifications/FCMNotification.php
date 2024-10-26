<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FCMNotification extends Notification
{
    use Queueable;

    protected array $notification;
    protected array $data;
    protected ?string $image;

    /**
     * Create a new notification instance.
     *
     * @param array $notification
     * @param array $data
     * @param string|null $image
     */
    public function __construct(array $notification, array $data, ?string $image)
    {
        $this->notification = $notification;
        $this->data = $data;
        $this->image = $image;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'notification' => $this->notification,
            'data' => $this->data,
            'image' => $this->image,
        ];
    }
}
