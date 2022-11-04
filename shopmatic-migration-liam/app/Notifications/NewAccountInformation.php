<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewAccountInformation extends Notification implements ShouldQueue
{
    use Queueable;
    
    protected $password;
    
    /**
     * Create a new notification instance.
     *
     * @param string $password
     */
    public function __construct($password)
    {
        $this->password = $password;
    }
    
    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }
    
    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('[New Account Information] Your account has been created')
            ->greeting('Hello ' . $notifiable->name)
            ->line('An account has been created for you.')
            ->line('You may login using the URL below')
            ->line('Your password is: ' . $this->password)
            ->action('Login', url(route('dashboard.index')))
            ->line('This is an automatic notification generated from the system.');
    }
}
