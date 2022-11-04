<?php

namespace App\Mail;

use App\Models\ProductInventory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowStockNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $inventory;
    protected $stock;

    /**
     * Create a new message instance.
     *
     * @param ProductInventory $inventory
     * @param $stock
     */
    public function __construct(ProductInventory $inventory, $stock)
    {
        $this->inventory = $inventory;
        $this->stock = $stock;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $log = $this->inventory->logs()->latest()->first();
        return $this->markdown('emails.inventory.low-stock')
            ->with(['inventory' => $this->inventory, 'log' => $log, 'stock' => $this->stock])
            ->subject('[Low Stock Notification] ' . $this->inventory->sku . ' has ' . $this->stock . ' left.');
    }
}
