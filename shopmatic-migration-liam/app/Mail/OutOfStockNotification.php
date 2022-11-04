<?php

namespace App\Mail;

use App\Models\ProductInventory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OutOfStockNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $inventory;

    /**
     * Create a new message instance.
     *
     * @param ProductInventory $inventory
     */
    public function __construct(ProductInventory $inventory)
    {
        $this->inventory = $inventory;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $log = $this->inventory->logs()->latest()->first();
        return $this->markdown('emails.inventory.out-of-stock')
            ->with(['inventory' => $this->inventory, 'log' => $log])
            ->subject('[Out of Stock Notification] ' . $this->inventory->sku .  ' is out of stock!');
    }
}
