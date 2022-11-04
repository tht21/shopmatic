<?php

namespace App\Integrations;

use App\Factories\ClientFactory;
use App\Interfaces\OrderAdapterInterface;
use App\Models\Account;
use App\Models\Order;
use App\Utilities\InternalResponse;
use Illuminate\Support\Facades\DB;

abstract class AbstractOrderAdapter extends InternalResponse implements OrderAdapterInterface
{

    /**
     * @var Account
     */
    protected $account;

    protected $client;

    /**
     * ProductAdapter constructor.
     *
     * @param Account|null $account
     *
     * @throws \Exception
     */
    public function __construct(Account $account, $client = null)
    {
        $this->account = $account;
        if (empty($client)) {
            $this->client = ClientFactory::create($account);
        } else {
            $this->client = $client;
        }
    }

    /**
     * Returns the Client object for the account
     *
     * @return AbstractClient|object
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Handles and creates the order and also perform any actions that needs to be done here
     *
     * @param TransformedOrder $order
     * @param array $options
     *
     * @return Order|void|null
     * @throws \Exception
     */
    public function handleOrder(TransformedOrder $order, $options) {

        // make sure orders placed at before the account integrated doesn't deduct the inventory
        if ($order->orderPlacedAt <= $this->account->created_at) {
            $options['deduct'] = false;
        }

        $createdOrder = null;
        DB::transaction(function() use ($order, $options, &$createdOrder) {
            $order = $order->createOrder($this->account, $options);
            $createdOrder = $order;
        });

        // TODO: Handle emails here and etc

        return $createdOrder;
    }

    /**
     * Creates the order on the marketplace
     *
     * @param Order $order
     * @throws \Exception
     */
    public function createOrder(Order $order)
    {
        throw new \Exception('Integration ' . $this->account->integration->name . ' does not support creating orders.');
    }

    /**
     * Updates the order on the marketplace
     *
     * @param Order $order
     * @throws \Exception
     */
    public function updateOrder(Order $order)
    {
        throw new \Exception('Integration ' . $this->account->integration->name . ' does not support creating orders.');
    }

}
