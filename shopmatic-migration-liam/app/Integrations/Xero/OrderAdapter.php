<?php

namespace Combinesell\Marketplaces\Xero;

use App\Constants\AccountStatus;
use App\Constants\IntegrationSyncData;
use App\Integration;
use App\Integrations\AbstractOrderAdapter;
use App\Integrations\TransformedOrder;
use App\Order;
use App\OrderItem;
use XeroPHP\Application;
use XeroPHP\Models\Accounting\Contact;
use XeroPHP\Models\Accounting\Invoice;
use XeroPHP\Remote\Response;

/**
 * @property Integration integration
 */
class OrderAdapter extends AbstractOrderAdapter
{

    /**
     * @return bool
     * @throws \Exception
     */
    public function sync() {
        throw new \Exception('Xero does not support syncing of orders.');
    }

    /*
     * Period exporting of orders
     * 
     * @throws \XeroPHP\Exception
     * @throws \XeroPHP\Remote\Exception
     * @throws \XeroPHP\Remote\Exception\NotFoundException
     */
    public function exportSync()
    {
        if (!$this->account->hasFeature(['orders', 'export_orders'])) {
            return;
        } elseif (!AccountStatus::ACTIVE()->same($this->account->status)) {
            return;
        }
        
        /** @var Application $xero */
        $xero = $this->client->xero;
        
        // We only want to push main orders
        // Get only orders pushed after last export, otherwise when first integration updated (In case they change the setting / etc)
        $from = $this->account->getSyncData(IntegrationSyncData::EXPORT_ORDERS(), $this->account->updated_at, true);
        
        $orders = $this->account->shop->orders()->whereNull('parent_id')->whereDate('updated_at', '>=', $from)->get();
        foreach ($orders as $order)
        {
            
        }
    }

    /**
     * Pushes the order (It needs to either be a Xero shadow order, or a main order)
     *
     * @param Order $order
     * @return \XeroPHP\Remote\Response|null
     * @throws \Exception
     */
    public function export(Order $order)
    {
        if (!empty($order->parent_id)) {
            if ($order->account_id != $this->account->id) {
                set_log_extra('order', $order);
                throw new \Exception('Unable to push non main order unless it\'s from the same account.');
            }
        }
        
        $invoice = $this->convertOrder($order);
        try {
            $response = $invoice->save();
            if ($response->getStatus() !== Response::STATUS_OK) {
                set_log_extra('response', $response->getResponseBody());
                set_log_extra('order', $order);
                set_log_extra('invoice', $invoice);
                set_log_extra('status', $response->getStatus());
                throw new \Exception('Unable to export invoice');
            }
            
        } catch (\Exception $e) {
            set_log_extra('order', $order);
            set_log_extra('invoice', $invoice);
            throw $e;
        }
    }

    /**
     * Transforms the order to the relevant invoice in Xero
     *
     * @param Order $order
     *
     * @return Invoice
     * @throws \XeroPHP\Remote\Exception
     */
    public function convertOrder(Order $order)
    {
        $xero = $this->client->xero;
        $invoice = new Invoice($xero);

        // Push each customer as a contact
        $pushAsCustomer = $this->account->getSetting(['orders', 'export_as_customer'], false);
        $contactId = null;
        if ($pushAsCustomer) {
            $name = $order->customer_name;
            $email = $order->customer_email;

            // This is if it's censored, we use the default details
            if (strpos($email, '*') !== false) {
                $email = 'support@combinesell.com';
                $name = 'CombineSell';
            }
        } else {
            if ($order->account) {
                $name = $order->account->name;
                $email = strtolower($order->integration->name) . '_integration@combinesell.com';
            } else {
                $name = config('app.name');
                $email = 'support@combinesell.com';
            }
        }

        /** @var \App\Contact $localContact */
        if ($localContact = $this->account->shop->contacts()->where('contact_email', $email)->first()) {
            $contactId = $localContact->getExternalId($this->account->id);
        }
        
        // Create locally and on Xero
        if (empty($contactId)) {
            $contact = new Contact($xero);
            $contact->setName($name);
            $contact->setEmailAddress($email);
            $contact->save();
            $contactId = $contact->getGUID();
            
            \App\Contact::createFromOrder($order, $contactId);
        }
        
        $contact = new Contact();
        $contact->setContactID($contactId);

        $status = 'AUTHORISED';

        /*
         * Account receivables. This is required for setting reference as well
         */
        $invoice->setType('ACCREC');
        $invoice->setContact($contact);

        $invoice->setStatus($status);
        $invoice->setLineAmountType('Inclusive');
        
        // This is if the order already exists on Xero, we'll be pushing it as an update
        if ($order->account_id === $this->account->id) {
            $invoice->setInvoiceID($order->external_id);
        }
        $invoice->setInvoiceNumber($order->id);
        $invoice->setReference($order->external_id ? $order->external_id . ($order->integration ? ' (' . $order->integration->name . ')' : '') : '');
        $invoice->setCurrencyCode($order->currency);
        $invoice->setDueDate($order->external_created_at ?? now());
        $invoice->setDate($order->external_created_at ?? now());

        $salesAccountCode = $this->integration->data['sales_account_code'] ?? 200;

        $pushCode = $this->integration->data['push_item_code'] ?? false;

        /** @var OrderItem $orderItem */
        foreach ($order->items as $orderItem) {
            $item = new Invoice\LineItem($xero);
            $item->setDescription(($orderItem->name ? $orderItem->name . '. ' : '') . 'SKU: ' . $orderItem->sku);
            $item->setQuantity($orderItem->quantity);
            $item->setLineAmount($orderItem->grand_total);
            $item->setUnitAmount($orderItem->item_price);
            $item->setAccountCode($salesAccountCode);

            if ($pushCode) {
                $item->setItemCode($orderItem->sku);
            }

            $invoice->addLineItem($item);
        }

        if ($order->seller_discount > 0){
            $item = new Invoice\LineItem($xero);
            $item->setDescription('Seller Discount');
            $item->setQuantity(1);
            $item->setLineAmount(-floatval($order->seller_discount));
            $item->setUnitAmount(-floatval($order->seller_discount));
            $item->setAccountCode($salesAccountCode);

            $invoice->addLineItem($item);
        }

        $negative = $this->integration->data['push_shipping_negative'] ?? false;

        $item = new Invoice\LineItem($xero);
        $item->setDescription('Shipping Fee');
        if ($this->integration->integration_order_stock_update) $item->setQuantity(1);
        $item->setLineAmount(empty($order->shipping_fee_seller) ? 0 :
            $negative ? -floatval($order->shipping_fee_seller) : floatval($order->shipping_fee_seller));
        $item->setUnitAmount(empty($order->shipping_fee_seller) ? 0 :
            $negative ? -floatval($order->shipping_fee_seller) : floatval($order->shipping_fee_seller));


        $shippingAccountCode = $this->integration->data['shipping_account_code'] ?? $salesAccountCode;

        $item->setAccountCode($shippingAccountCode);

        $invoice->addLineItem($item);


        // Platform Fees
        if ($fee = ($order->payment_fee + $order->commission_fee)) {
            $negative = $this->integration->data['push_fees_negative'] ?? false;

            $item = new Invoice\LineItem($xero);
            $item->setDescription('Platform Fees');
            if ($this->integration->integration_order_stock_update) $item->setQuantity(1);
            $item->setLineAmount( $negative ? -floatval($fee) : floatval($fee));
            $item->setUnitAmount($negative ? -floatval($fee) : floatval($fee));

            $feeAccountCode = $this->integration->data['platform_fees_account_code'] ?? $salesAccountCode;

            $item->setAccountCode($feeAccountCode);

            $invoice->addLineItem($item);
        }
        
        $invoice->setUrl(url(route('dashboard.orders.show', [$order->getRouteKey()])));
        
        return $invoice;
    }

    /**
     * This transforms the Invoice object from Xero into our Order
     * 
     * @param $invoice
     * @return TransformedOrder|void
     */
    public function transformOrder($invoice)
    {
        
    }

    /**
     * Retrieves a single order
     *
     * @param string $externalId
     * @param array $options
     * @return Order
     */
    public function get($externalId, $options = ['deduct' => true])
    {
        
    }

    /**
     * Imports all orders
     *
     * @param array $options
     * @return void
     * @throws \Exception
     */
    public function import($options = ['deduct' => false])
    {
        throw new \Exception('Xero does not support order import.');
    }

    /**
     * @param $order
     *
     * @return array
     */
    public function availableActions(Order $order)
    {
        // TODO: Implement availableActions() method.
    }
}