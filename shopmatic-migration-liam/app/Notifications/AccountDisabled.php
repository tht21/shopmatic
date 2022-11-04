<?php

namespace App\Notifications;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

class AccountDisabled extends Notification
{
    use Queueable;

    protected $account;

    /**
     * Create a new notification instance.
     *
     * @param Account $account
     */
    public function __construct(Account $account)
    {
        $this->account = $account;
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
                ->subject('Alert: '.$this->account->integration->name.' account deactivation notice ')
                ->greeting('Dear seller,')
                ->line('We notice your '.$this->account->integration->name.' account integration with Combinesell is de-activated.')
                ->line('Please note that syncing of inventory, orders and products will be affected for this account.')
                ->line(new HtmlString('<li>'.$this->account->integration->name.' account - '.$this->account->name.'</li>'))
                ->line('To continue seemless operations, please re-activate the account.')
                ->action('View', url(route('dashboard.accounts.index')))
                ->salutation(new HtmlString('Regards <br> The Combinesell Team'));
    }

}
