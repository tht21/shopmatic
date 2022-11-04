<?php

namespace App\Events;

use App\Constants\ProductAlertType;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class NewProductAlert
{
    use Dispatchable, SerializesModels;
    
    public $product;
    public $message;
    public $type;
    public $shopId;
    public $productId;
    
    /**
     * Create a new event instance.
     *
     * @param $product
     * @param $message
     * @param ProductAlertType $type
     */
    public function __construct($product, $message, ProductAlertType $type, $shopId = null, $productId = null)
    {
        $this->product = $product;
        $this->message = $message;
        $this->type = $type;
        $this->shopId = $shopId;
        $this->productId = $productId;
    }
}
