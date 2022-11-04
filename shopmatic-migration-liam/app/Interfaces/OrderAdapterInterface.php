<?php
namespace App\Interfaces;

use App\Integrations\TransformedOrder;
use App\Models\Order;

interface OrderAdapterInterface
{

    /**
     * Retrieves a single order
     *
     * @param string $externalId
     * @param array $options
     * @return Order
     */
    public function get($externalId, $options = ['deduct' => true]);

    /**
     * Imports all orders
     *
     * @param array $options
     * @return void
     */
    public function import($options = ['deduct' => false]);

    /**
     * Incremental sync for orders
     *
     * @return mixed
     */
    public function sync();

    /**
     * @param $order
     *
     * @return TransformedOrder
     */
    public function transformOrder($order);

    /**
     * @param $order
     *
     * @return array
     */
    public function availableActions(Order $order);

}
