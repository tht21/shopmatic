<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class NewOrderNotification extends Mailable
{
    use Queueable;

    protected $order;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        try {
            return $this->markdown('emails.order.new-order')
                ->with(['order' => $this->order])
                ->subject('[New Order] Order #' . $this->order->external_order_number . ' from ' . $this->order->account->integration->name . '.');
        } catch (Exception $e) {
            $debugLog = '[NewOrderNotification]Debug Log|Order Id|'.$this->order->id.'|Message|'. $e->getMessage();
            \Log::error($debugLog);
            throw new Exception($debugLog);
        }
    }
}
