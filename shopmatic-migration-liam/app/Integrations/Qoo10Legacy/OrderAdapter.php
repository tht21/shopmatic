<?php

namespace App\Integrations\Qoo10Legacy;

use App\Constants\FulfillmentStatus;
use App\Constants\FulfillmentType;
use App\Constants\IntegrationSyncData;
use App\Constants\OrderType;
use App\Constants\PaymentStatus;
use App\Constants\ProductIdentifier;
use App\Integrations\AbstractOrderAdapter;
use App\Integrations\TransformedAddress;
use App\Integrations\TransformedOrder;
use App\Integrations\TransformedOrderItem;
use App\Models\Order;
use App\Models\ProductListing;
use App\Models\Region;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class OrderAdapter extends AbstractOrderAdapter
{

    /**
     * Retrieves a single order
     *
     * @param $externalId
     * @param array $options
     * @return bool
     * @throws \Exception
     */
    public function get($externalId, $options = ['deduct' => true])
    {
        try {
            $parameters = [
                'order_id' => $externalId,
                'start_date' => Carbon::now()->subMonths(3), // Maximum search previous 3 months order
                'end_date' => Carbon::now(),
                // Status Mapping
                'filters' => [
                    ['data_type' => 'DT1', 'status' => 'D1'], // pending
                    ['data_type' => 'DT4', 'status' => 'D2'], // paid
                    ['data_type' => 'DT2', 'status' => 'D3'], // shipped
                    ['data_type' => 'DT5', 'status' => 'D4'] // delivered
                ]
            ];

            $this->fetchOrders($options, $parameters);
        } catch(\Exception $e) {
            set_log_extra('response', $e);
            throw new \Exception('Woocommerce-'.$this->account->id.' Unable to connect and retrieve order.');
        }
        return true;
    }

    /**
     * Import all orders
     *
     * @param array $options
     * @return void
     * @throws \Exception
     */
    public function import($options = ['deduct' => false])
    {
        // This is so it wont create new notifications
        $options['import'] = true;
        if (!isset($options['deduct'])) {
            $options['deduct'] = false;
        }

        $parameters = [
            'start_date' => new Carbon('last year'),
            'end_date' => Carbon::now(),
            // Status Mapping
            'filters' => [
                ['data_type' => 'DT1', 'status' => 'D1'], // pending
                ['data_type' => 'DT4', 'status' => 'D2'], // paid
                ['data_type' => 'DT2', 'status' => 'D3'], // shipped
                ['data_type' => 'DT5', 'status' => 'D4'] // delivered
            ]
        ];

        $this->fetchOrders($options, $parameters);
    }

    /**
     * Incremental order sync
     *
     * @throws \Exception
     */
    public function sync()
    {
        $options['import'] = false;
        $options['deduct'] = $this->account->hasFeature(['orders', 'deduct_inventory']);

        $parameters = [
            'start_date' => $this->account->getSyncData(IntegrationSyncData::SYNC_ORDERS(), now(), true),
            'end_date' => Carbon::now(),
            // Status Mapping
            'filters' => [
                ['data_type' => 'DT1', 'status' => 'D1'], // pending
                ['data_type' => 'DT4', 'status' => 'D2'], // paid
                ['data_type' => 'DT2', 'status' => 'D3'], // shipped
                ['data_type' => 'DT5', 'status' => 'D4'] // delivered
            ]
        ];

        $this->fetchOrders($options, $parameters);

        $this->account->setSyncData(IntegrationSyncData::SYNC_ORDERS(), now());
    }

    /**
     * This is used by both import and sync as their code is the same, the only difference is the timestamps
     *
     * @param $options
     * @param $parameters
     * @throws \Exception
     */
    private function fetchOrders($options, $parameters)
    {
        $start = ($parameters['start_date']) ?? Carbon::now()->subDays(30);
        $end = ($parameters['end_date']) ?? Carbon::now();
        $orderId = ($parameters['order_id']) ?? null;

        $deductibleAmount = $this->getDeductibleAmount($start->format("M d, Y"), $end->format("M d, Y"));
        $Qoo10ServiceFee = $this->getServiceFee($start->format("M d, Y"), $end->format("M d, Y"));


        // Get cancelled orders
        $this->getCancelOrder([
            'start_date' => $start->format('M d, Y H:i:s'),
            'end_date'   => $end->format('M d, Y H:i:s'),
            'order_id'   => $orderId
        ]);


        try {
            foreach ($parameters['filters'] as $filter) {
                $orders = $this->getOrders([
                    'date_type'  => $filter['data_type'],
                    'status'     => $filter['status'],
                    'start_date' => $start->format('M d, Y H:i:s'),
                    'end_date'   => $end->format('M d, Y H:i:s'),
                    'order_id'   => $orderId
                ]);

                if (!is_array($orders) || empty($orders)) {
                    continue;
                }

                foreach ($orders as $key => $order) {
                    // Get amount of details
                    $orders[$key]['amount_details'] = $this->getAmountDetails($order['order_no']);

                    try {
                        $order = $this->transformOrder($order, $deductibleAmount, $Qoo10ServiceFee);

                    } catch (\Exception $e) {
                        set_log_extra('order', $order);
                        throw $e;
                    }
                    $this->handleOrder($order, $options);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Retrieve order listing
     *
     * @param $parameters
     * @return array
     * @throws \Exception
     */
    private function getOrders($parameters)
    {
        $startDate  = $parameters['start_date'];
        $endDate    = $parameters['end_date'];
        $searchKeyword = ($parameters['order_id']) ?? '';
        $cache      = substr(str_replace(".", "", microtime(true)),0,-1);

        /* DT1 - '' - All (without any date), Order Date, DT4 - Payment Complete, DT2 - Shipping Date, DT5 - Delivered Date, DT6 - Estimated Shipping Date */
        $dateType = $parameters['date_type'];
        /* '' - All, D1 - Awaiting Payment, D2 - On Request, D3 - On Delivery, D4 - Delivered */
        $status = $parameters['status'];

        $response = $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_DynamicDataService.asmx/ExecuteToDataTable', [
            'json' => [
                'id' => 'Shipping.GetTransportListPreV2',
                'paramList' => [
                    'ParamList' => [
                        [
                            'Name'  => 'delivery_type',
                            'Value' => 'S'
                        ],
                        [
                            'Name'  => 'stat',
                            'Value' => $status
                        ],
                        [
                            'Name'  => 'stat_option',
                            'Value' => 'ALL'
                        ],
                        [
                            'Name'  => 'transc_cd',
                            'Value' => ''
                        ],
                        [
                            'Name'  => 'srch_check',
                            'Value' => 'Y'
                        ],
                        [
                            'Name'  => 'date_type',
                            'Value' => $dateType
                        ],
                        [
                            'Name'  => 'sdate',
                            'Value' => $startDate
                        ],
                        [
                            'Name'  => 'edate',
                            'Value' => $endDate
                        ],
                        [
                            'Name'  => 'srch_option',
                            'Value' => 'CONO'
                        ],
                        [
                            'Name'  => 'srch_keyword',
                            'Value' => $searchKeyword
                        ],
                        [
                            'Name'  => 'item_option_info',
                            'Value' => ''
                        ],
                        [
                            'Name'  => 'svc_nation_cd',
                            'Value' => ''
                        ],
                        [
                            'Name'  => 'excel_type',
                            'Value' => 'Y'
                        ],
                        [
                            'Name'  => 'sort_type',
                            'Value' => 'DEFAULT'
                        ],
                        [
                            'Name'  => 'sz_set_yn',
                            'Value' => ''
                        ],
                        [
                            'Name'  => 'vendor_type',
                            'Value' => ''
                        ],
                        [
                            'Name'  => 'vendor_branch_cd',
                            'Value' => ''
                        ],
                        [
                            'Name'  => 'qoo10_disp_addr_type',
                            'Value' => 'D'
                        ],
                        [
                            'Name'  => 'qwms_yn',
                            'Value' => 'Y'
                        ],
                        [
                            'Name'  => 'qstore_yn',
                            'Value' => 'N'
                        ]
                    ]
                ],
                '___cache_expire___' => $cache
            ]
        ]);

        $orders = [];
//        if (!is_null($response) && isset($response) && $response->getStatusCode() === Response::HTTP_OK) {
            $response = json_decode($response->getBody(), true);

            if (isset($response['d'])) {
                $orders = $response['d']['ReturnData']['Rows'];
            }
            return $orders;
//        } else {
//            set_log_extra('response', $response);
//            set_log_extra('parameter', $parameters);
//            set_log_extra('status', $response->getStatusCode());
//            set_log_extra('body', json_decode($response->getBody(), true));
//            throw new \Exception('Unable to retrieve orders items for Qoo10 Legacy');
//        }
    }

    /*
    * Get order amount details
    *
    */
    public function getAmountDetails($orderNo)
    {
        $cache = substr(str_replace(".", "", microtime(true)),0,-1);
        $region = ($this->account->region_id == Region::SINGAPORE) ? 'SG' : 'MY';

        $response =  $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_AccountBizService.asmx/GetContrMoneyOne', [
            'json' => [
                'contr_no' => $orderNo,
                'kind'=> '1',
                'svc_nation_cd'=> $region,
                '___cache_expire___' => $cache
            ]
        ]);

        $response = json_decode($response->getBody(), true);

        $data = [];
        if (isset($response['d'])) {
            if (!isset($response['d']['Rows'][0])) {
                set_log_extra('order_amount_details', $response);
                throw new \Exception('Unable to retrieve order amount details for Qoo10 Legacy');
            }
            $data = $response['d']['Rows'][0] ?? [];
        }
        return $data;
    }

    /*
     * Get cancel order, the get order doesnt return cancelled order
     *
     * http://prntscr.com/pt0pwm
     * http://prntscr.com/pt0q1e
     *
     */
    public function getCancelOrder($parameters)
    {
        $startDate  = $parameters['start_date'];
        $endDate    = $parameters['end_date'];
        $orderId    = ($parameters['order_id']) ?? '';
        $cache      = substr(str_replace(".", "", microtime(true)),0,-1);

        $response =  $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_ClaimBizService.asmx/GetClaimList', [
            'json' => [
                'claim_sol_stat' => 'S4',
                'claim_type' => 'CN',
                'claim_why' => '',
                'cust_no' => $this->client->getSellerNo(),
                'date_type' => 'DT3',
                'eday' => $endDate,
                'sday' => $startDate,
                'srch_option' => 'contr_no',
                'srch_option_value' => $orderId,
                'svc_nation_cd' => '',
                'transfer_stat' => '',
                '___cache_expire___' => $cache
            ]
        ]);

        $response = json_decode($response->getBody(), true);

        if (isset($response['d'])) {
            $orders = $response['d']['Rows'];

            foreach ($orders as $order) {
                $externalId = $order['contr_no'];

                $this->updateOrderCancel($externalId);
            }
        }
    }

    /**
     * Update order to cancel
     *
     * @param $externalId
     */
    public function updateOrderCancel($externalId)
    {
        /** @var Order $order */
        $order = Order::where([
            'external_id' => $externalId,
            'account_id' => $this->account->id
        ])->first();

        if ($order) {
            $order->payment_status = PaymentStatus::CANCELLED()->getValue();
            $order->fulfillment_status = FulfillmentStatus::CANCELLED()->getValue();
            $order->save();

            $order->items()->update([
                'fulfillment_status' => FulfillmentStatus::CANCELLED()->getValue()
            ]);
        }
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function transformOrder($order, $deductibleAmountList = [], $serviceFeeList = [] )
    {
        $externalId = $order['order_no'];
        $externalNumber = null;
        $externalSource = $this->account->integration->name;

        $customerName = $order['b_cust_nm'] ?? null;
        $customerEmail = $order['b_e_mail'] ?? null;

        // To remove any if it's empty
        $shippingFullAddress = ($order['r_del_front_address'] ?? '').' '.($order['r_del_back_address'] ?? '');
        $shippingAddress = new TransformedAddress(null, $order['rcv_nm'],
            $shippingFullAddress, null, null, null, null,
            null, $order['r_zip_code'], null, $order['nation_nm'], $order['r_hp_no']
        );
        $billingAddress = new TransformedAddress(null, $order['b_cust_nm'],
            null, null, null, null, null,
            null, null, null, null, $order['b_hp_no']
        );

        if (isset($order['ttrans_dt']) && !empty($order['ttrans_dt'])) {
            $shipByDate = Carbon::createFromFormat('Ymd', $order['ttrans_dt'])->addDays(3);
        } else {
            $shipByDate = null;
        }

        $paymentStatus = PaymentStatus::PAID();
        // If order status is unpaid, then it will be unpaid status
        if ($order['stat'] == 'D1') {
            $paymentStatus = PaymentStatus::UNPAID();
        }

        $currency = $this->account->currency;

        $integrationDiscount = 0;
        $sellerDiscount = 0;
        $shippingFee = 0;
        $tax = 0;
        $tax2 = 0;

        $commission = 0;
        if (isset($order['order_no']) && isset($serviceFeeList[$order['order_no']])) {
            //$commission =  $serviceFeeList[$order['order_no']];
        }
        $transactionFee = 0;

        $settlementAmount = abs($order['amount_details']['sttl_total'] ?? $order['acnt_money']);

        $paymentMethod = null;

        $fulfillmentType = FulfillmentType::REQUIRES_SHIPPING();

        if ($order['stat'] == 'D1') {
            $fulfillmentStatus = FulfillmentStatus::PENDING();
        } elseif ($order['stat'] == 'D2') {
            $fulfillmentStatus = FulfillmentStatus::PROCESSING();
        } elseif ($order['stat'] == 'D3') {
            $fulfillmentStatus = FulfillmentStatus::SHIPPED();
        } elseif ($order['stat'] == 'D4') {
            $fulfillmentStatus = FulfillmentStatus::DELIVERED();
        } else {
            set_log_extra('order_status', $order['stat']);
            set_log_extra('order', $order);
            throw new \Exception('Qoo10 Legacy has different fulfilment status');
        }

        $paidAmount = abs($order['amount_details']['trade_total'] ?? $order['acnt_money']);
        $paidAmount = $paidAmount < $settlementAmount ? $settlementAmount : $paidAmount;
        $buyerPaid = $paymentStatus->equals(PaymentStatus::PAID()) ? $paidAmount : 0;

        // This is customer/buyer remark or message
        $buyerRemarks = $order['del_memo'] ?? null;

        if (isset($order['delivery_group_no']) && !empty($order['delivery_group_no'])) {
            $buyerRemarks .= ' -- Delivery Group No: ' . $order['delivery_group_no'];
        }
        if (isset($order['delivery_branch_transc_nm']) && !empty($order['delivery_branch_transc_nm'])) {
            $buyerRemarks .= ' -- Delivery Branch: ' . $order['delivery_branch_transc_nm'];
        }

        $type = OrderType::NORMAL();

        $orderPlacedAt = new Carbon($order['contr_dt']);
        $orderUpdatedAt = new Carbon($order['contr_dt']);

        $orderPaidAt = null;

        $data = [
            'pack_no' => $order['pack_no'] ?? '',
            'transc_cd' => $order['transc_cd'] ?? '',
            'sell_cust_no' => $order['sell_cust_no'] ?? '',
            'no_songjang' => $order['no_songjang'] ?? '',
            'order_transc_cd' => $order['order_transc_cd'] ?? '',
        ];

        // Get the order items
        $sku = $order['option_code_info'] ?? $order['outer_gd_no']; // variant sku ?? main sku
        // order product id
        $goodNo = $order['gd_no'] ?? null;

        // if order product id and sku found
        if (!empty($goodNo) && !empty($sku)) {
            // find listing with qoo10 order product id
            $productListing = ProductListing::whereAccountId($this->account->id)
                ->whereIntegrationId($this->account->integration_id)
                ->where('identifiers->external_id', $goodNo)
                ->first();

            if ($productListing) {
                // find variant listing with same sku as qoo10 order product
                /** @var ProductListing $productListingVariant */
                $productListingVariant = $productListing->listing_variants->where('identifiers->sku', $sku)->first();
                if ($productListingVariant) {
                    $goodNo = $productListingVariant->getIdentifier(ProductIdentifier::EXTERNAL_ID());
                }
            }
        }

        /*
         * For same item which are sold in bundled, separated by comma
         * Eg. abc,abc - item price should be paid price / count - quantity should be count
         */
        $itemSku = $sku;
        $quantity = $order['contr_amt'] ?? 0;
        $itemPrice = abs($order['acnt_money']) / $quantity;
        $paidPrice =  abs($order['acnt_money']);

        $itemExternalId = $order['gd_no'] ?? null;
        $itemName = $order['gd_nm'] ?? null;
        $externalProductId = $goodNo;
        $variationName = 'N/A';
        $variationSku = $itemSku;

        $itemIntegrationDiscount = 0;
        $itemSellerDiscount = 0;

        $itemShippingFee = 0;

        $itemTax = 0;
        $itemTax2 = 0;

        $itemGrandTotal = $paidPrice;
        $itemBuyerPaid = $paymentStatus->equals(PaymentStatus::PAID()) ? $itemGrandTotal : 0;

        $grandTotal = $itemGrandTotal;

        $itemFulfillmentStatus = $fulfillmentStatus;

        $shipmentProvider = $order['takbae_name'] ?? 'Store Pickup';
        $trackingNumber = $order['packing_no'] ?? null;
        $shipmentType = null;
        $shipmentMethod = null;

        $returnStatus = null;
        $costOfGoods = null;
        $actualShippingFee = $shippingFee;

        $itemData = [];
        $items = [];

        $skus = explode(',', $sku);
        // If account have handle bundled sku feature and sku contains , means its bundled sku
        if ($this->account->hasFeature(['orders', 'handle_bundled_sku']) && count($skus) > 1) {
            foreach ($skus as $key => $value) {
                // new to have a dummy external Id, else it will not be created
                $variantExternalId = null;
                $variantName = $itemName.' ('.$value.')';
                // get listing based on the sku
                $listing = ProductListing::whereAccountId($this->account->id)
                    ->whereIntegrationId($this->account->integration_id)
                    ->where('identifiers->sku', $value)
                    ->first();
                if ($listing) {
                    $variantExternalId = $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());
                    $variantName = $listing->name;
                }

                $items[] = new TransformedOrderItem($itemExternalId.'_'.$key, $variantExternalId, $variantName, $value, $variationName, $value, $quantity,
                    0, 0, 0, 0, 0, 0, 0, 0,
                    $itemFulfillmentStatus, $shipmentProvider, $shipmentType, $shipmentMethod, $trackingNumber, $returnStatus, 0, 0,
                    $itemData);
            }
        } else {
            $items[] = new TransformedOrderItem($itemExternalId, $externalProductId, $itemName, $sku, $variationName, $variationSku, $quantity,
                $itemPrice, $itemIntegrationDiscount, $itemSellerDiscount, $itemShippingFee, $itemTax, $itemTax2, $itemGrandTotal, $itemBuyerPaid,
                $itemFulfillmentStatus, $shipmentProvider, $shipmentType, $shipmentMethod, $trackingNumber, $returnStatus, $costOfGoods, $actualShippingFee,
                $itemData);
        }

        $order = new TransformedOrder($externalId, $externalSource, $externalNumber, $customerName, $customerEmail, $shippingAddress, $billingAddress,
            $shipByDate, $currency, $integrationDiscount, $sellerDiscount, $shippingFee, $tax, $tax2, $commission, $transactionFee, $grandTotal, $buyerPaid, $settlementAmount,
            $paymentStatus, $paymentMethod, $fulfillmentType, $fulfillmentStatus, $buyerRemarks, $type, $data, $orderPlacedAt,
            $orderUpdatedAt, $orderPaidAt, $items);

        return $order;
    }

    /**
     * Can combine with getServiceFee
     *
     * Settlement > Sales Summary > Deductible detail
     * Get deductible amount from Deductible detail table
     *
     * @param $start
     * @param $end
     * @return array
     */
    public function getDeductibleAmount($start, $end)
    {
        $cache = substr(str_replace(".", "", microtime(true)),0,-1);
        $region = ($this->account->region_id == Region::SINGAPORE) ? 'SG' : 'MY';

        $response = $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_DynamicDataService.asmx/ExecuteToDataTable', [
            'json' => [
                'id' => 'Account.GetSellingReportRecvDetailList',
                'paramList' => [
                    'ParamList' => [
                        [
                            'Name'  => 'cust_no',
                            'Value' => $this->client->getSellerNo()
                        ],
                        [
                            'Name'  => 'srch_dt',
                            'Value' => 'DT1'
                        ],
                        [
                            'Name'  => 'sday',
                            'Value' => $start
                        ],
                        [
                            'Name'  => 'eday',
                            'Value' => $end
                        ],
                        [
                            'Name'  => 'srch_type',
                            'Value' => 'REF_NO'
                        ],
                        [
                            'Name'  => 'srch_value',
                            'Value' => ''
                        ],
                        [
                            'Name'  => 'srch_kind',
                            'Value' => ''
                        ],
                        [
                            'Name'  => 'svc_nation_cd',
                            'Value' => $region
                        ],
                        [
                            'Name'  => 'currency_cd',
                            'Value' => $this->account->currency
                        ],
                        [
                            'Name'  => 'sttl_stat',
                            'Value' => ''
                        ],
                        [
                            'Name'  => 'jp_yn',
                            'Value' => 'N'
                        ],
                    ]
                ],
                '___cache_expire___' => $cache
            ]
            //json_decode('{"id":"Account.GetSellingReportRecvDetailList", "paramList":{"ParamList":[{"Name":"cust_no", "Value":"'.$this->client->getSellerNo().'"},{"Name":"srch_dt", "Value":"DT1"},{"Name":"sday", "Value":"'.$start.'"},{"Name":"eday", "Value":"'.$end.'"},{"Name":"srch_type", "Value":"REF_NO"},{"Name":"srch_value", "Value":""},{"Name":"srch_kind", "Value":""},{"Name":"svc_nation_cd", "Value":"'.$this->region.'"},{"Name":"currency_cd", "Value":"'.$this->currency.'"},{"Name":"sttl_stat", "Value":""},{"Name":"jp_yn", "Value":"N"}]}, "___cache_expire___":"'.$cache.'"}')
        ]);

        $response = json_decode($response->getBody(), true);

        $trackingNoToDeductibleAmount = [];
        if (isset($response['d']) && isset($response['d']['ReturnData'])) {

            $trackingNoToDeductibleAmount = collect($response['d']['ReturnData']['Rows'])->mapWithKeys(function ($item) {
                return [$item['no_songjang'] => $item['acc_recv_amount']];
            })->toArray();

            /*foreach ($response['d']['ReturnData']['Rows'] as $item) {
                $trackingNoToDeductibleAmount[$item['no_songjang']] = $item['acc_recv_amount'];
            }*/
        }
        return $trackingNoToDeductibleAmount;
    }

    /**
     * Can combine with getDeductibleAmount
     *
     * Settlement > Sales Summary > Item sales detail
     * Get deductible amount from Item sales detail table
     *
     * @param $start
     * @param $end
     * @return array
     */
    public function getServiceFee($start, $end)
    {
        $cache = substr(str_replace(".", "", microtime(true)),0,-1);
        $region = ($this->account->region_id == Region::SINGAPORE) ? 'SG' : 'MY';

        $response = $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_DynamicDataService.asmx/ExecuteToDataTable', [
            'json' => [
                'id' => 'Account.GetSellingReportDetailList',
                'paramList' => [
                    'ParamList' => [
                        [
                            'Name'  => 'cust_no',
                            'Value' => $this->client->getSellerNo()
                        ],
                        [
                            'Name'  => 'srch_dt',
                            'Value' => 'DT1'
                        ],
                        [
                            'Name'  => 'sday',
                            'Value' => $start
                        ],
                        [
                            'Name'  => 'eday',
                            'Value' => $end
                        ],
                        [
                            'Name'  => 'srch_type',
                            'Value' => 'REF_NO'
                        ],
                        [
                            'Name'  => 'srch_value',
                            'Value' => ''
                        ],
                        [
                            'Name'  => 'srch_kind',
                            'Value' => ''
                        ],
                        [
                            'Name'  => 'svc_nation_cd',
                            'Value' => $region
                        ],
                        [
                            'Name'  => 'currency_cd',
                            'Value' => $this->account->currency
                        ],
                        [
                            'Name'  => 'sttl_stat',
                            'Value' => ''
                        ],
                        [
                            'Name'  => 'cod_type',
                            'Value' => 'N'
                        ],
                        [
                            'Name'  => 'jp_yn',
                            'Value' => 'N'
                        ],
                    ]
                ],
                '___cache_expire___' => $cache
            ]
            //'json' => json_decode('{"id":"Account.GetSellingReportDetailList", "paramList":{"ParamList":[{"Name":"cust_no", "Value":"'.$this->client->getSellerNo().'"},{"Name":"srch_dt", "Value":"DT1"},{"Name":"sday", "Value":"'.$start.'"},{"Name":"eday", "Value":"'.$end.'"},{"Name":"srch_type", "Value":""},{"Name":"srch_value", "Value":""},{"Name":"srch_kind", "Value":""},{"Name":"svc_nation_cd", "Value":"'.$this->region.'"},{"Name":"currency_cd", "Value":"'.$this->currency.'"},{"Name":"sttl_stat", "Value":""},{"Name":"cod_type", "Value":"N"},{"Name":"jp_yn", "Value":"N"}]}, "___cache_expire___":"'.$cache.'"}')
        ]);

        $response = json_decode($response->getBody(), true);

        $serviceFee = [];
        if (isset($response['d']) && isset($response['d']['ReturnData'])) {

            $serviceFee = collect($response['d']['ReturnData']['Rows'])->mapWithKeys(function ($item) {
                return [$item['contr_no'] => $item['commission']];
            })->toArray();

            /*foreach ($response['d']['ReturnData']['Rows'] as $item) {
                $serviceFee[$item['contr_no']] = $item['commission'];
            }*/
        }
        return $serviceFee;
    }

    /**
     * @inheritDoc
     */
    public function availableActions(Order $order)
    {
        // General actions are those that can be called regardless of status
        $general = [''];

        // These are status specific in which they depend on the status of the order
        $statusSpecific = [];

        if ($order->fulfillment_status >= FulfillmentStatus::PENDING()->getValue() && $order->fulfillment_status < FulfillmentStatus::CANCELLED()->getValue()) {
            $statusSpecific[] = 'printAddress';
        }

        if ($order->fulfillment_status >= FulfillmentStatus::PROCESSING()->getValue() && $order->fulfillment_status < FulfillmentStatus::CANCELLED()->getValue()) {
            $statusSpecific[] = 'shippingStatement';
        }

        if ($order->fulfillment_status === FulfillmentStatus::PENDING()->getValue()) {
            $statusSpecific[] = 'reasons';
            $statusSpecific[] = 'cancel';
        } else if ($order->fulfillment_status === FulfillmentStatus::PROCESSING()->getValue()) {
            $statusSpecific[] = 'reasons';
            $statusSpecific[] = 'cancel';
            $statusSpecific[] = 'airwayBill';
        }

        foreach ($order->items as $item) {
            // seller delivery should not be able to print shipping statement, else it will be changed to qxpress
            if ($item->shipment_provider === 'Seller Delivery') {
                $key = array_search('shippingStatement', $statusSpecific);
                if ($key !== false) {
                    unset($statusSpecific[$key]);
                }
            }
            if ($item->fulfillment_status === FulfillmentStatus::PROCESSING()->getValue() && $item->shipment_provider === 'Seller Delivery') {
                if (!in_array('updateEstimatedShippingDate', $statusSpecific)) {
                    $statusSpecific[] = 'updateEstimatedShippingDate';
                }
            }
            if ($item->fulfillment_status === FulfillmentStatus::READY_TO_SHIP()->getValue() && $item->shipment_provider === 'Seller Delivery') {
                if (!in_array('getDeliveryCompanyList', $statusSpecific)) {
                    $statusSpecific[] = 'getDeliveryCompanyList';
                }
                if (!in_array('fulfillment', $statusSpecific)) {
                    $statusSpecific[] = 'fulfillment';
                }
            }
            if ($item->fulfillment_status === FulfillmentStatus::PROCESSING()->getValue() && ($item->shipment_provider === 'Qxpress' || $item->shipment_provider === 'Qprime')) {
                if (!in_array('getLogistic', $statusSpecific)) {
                    $statusSpecific[] = 'getLogistic';
                }
                if (!in_array('pickup', $statusSpecific)) {
                    $statusSpecific[] = 'pickup';
                }
            }
        }

        return array_merge($general, $statusSpecific);
    }

    /**
     * Retrieve order airway bill
     *
     * @param $orders
     * @param Request $request
     * @return mixed|string
     */
    public function airwayBill($orders, Request $request)
    {
        $isBulk = $request->get('is_bulk', false);

        $allOrders = [];
        // For bulk orders
        if ($isBulk) {
            foreach ($orders as $order) {
                $allOrders[] = Order::whereId($order)->first();
            }
        } else {
            // For single order
            $allOrders[] = $orders;
        }

        $documents = [];
        foreach ($allOrders as $order) {
            try {
                // Set airway bill to printed
                $this->setAirwayBillPrinted($order);

                $codes = $order->items()->whereNotNull('tracking_number')->groupBy('tracking_number')->pluck('tracking_number');

                $response =  $this->client->request('post', 'GMKT.INC.Gsm.Web/PopUp/Delivery/PrintPackingQxpress.aspx', [
                    'form_params' => [
                        'h_packing_arr' => $codes->implode(','),
                        'h_packing_idt' => ''
                    ]
                ]);

                if ($response->getStatusCode() === Response::HTTP_OK) {
                    $waybill = $response->getBody();
                    $waybill= str_replace('scripts/printpackingqxpress.js','https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/PopUp/Delivery/scripts/printpackingqxpress.js', $waybill);
                    $waybill = str_replace('/gmkt.inc.gsm.web','https://qsm.qoo10.sg/gmkt.inc.gsm.web', $waybill);

                    $dom = new \DOMDocument();
                    $dom->loadHTML($waybill);
                    $documents[] = $dom->saveHTML();
                    //return $this->parseAirwayBillDocument([$document]);
                } else {
                    set_log_extra('response', $response);
                    throw new \Exception('Unable to retrieve airway bill for Qoo10 Legacy');
                }
            } catch (\Exception $exception) {
                return $this->respondBadRequestError($exception->getMessage());
            }
        }

        return $this->parseAirwayBillDocument($documents);
    }

    /**
     * Set order airway bill is printed
     *
     * @param $order
     * @return bool
     * @throws \Exception
     */
    public function setAirwayBillPrinted($order)
    {
        $sellerNo = $this->getSellerNo();
        $codes = $order->items()->whereNotNull('tracking_number')->groupBy('tracking_number')->pluck('tracking_number');
        $cache = Carbon::now()->format('D M d Y H:i:s GMT+0800 (+08)');

        foreach ($codes as $value) {
            $response = $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_DeliveryBizService.asmx/SetQxpressPrintYN', [
                'json' => [
                    'packing_no'=> $value,
                    'type_cd'   => '3',
                    'user_id'   => $sellerNo,
                    '___cache_expire___' => $cache
                ]
            ]);

            if (!isset($response) || $response->getStatusCode() !== Response::HTTP_OK) {
                set_log_extra('response', $response);
                throw new \Exception('Unable to set airway bill printed for Qoo10 Legacy');
            }
        }
        return true;
    }

    /**
     * Parse Airwaybill Document to display single or multiple documents
     *
     * @param array $documents
     * @return array|string
     */
    public function parseAirwayBillDocument($documents = [])
    {
        if (empty($documents)) {
            return [];
        }
        $dom = new \DomDocument();
        foreach ($documents as $key => $waybill) {
            if ($key == 0) {
                $dom->loadHTML($waybill);
                /*foreach ($dom->getElementsByTagName('link') as $style) {
                    if ($style->attributes->getNamedItem('rel')->value == 'Stylesheet') {
                        $href = $style->attributes->getNamedItem('href')->value;
                        if (substr($href, 0, 1) == '/')
                            $style->setAttribute('href', 'https:' . $href);
                    }
                }
                foreach ($dom->getElementsByTagName('img') as $img) {
                    if ($img->attributes->getNamedItem('alt')->value == 'Qxpress') {
                        $src = $img->attributes->getNamedItem('src')->value;
                        if (substr($href, 0, 1) == '/')
                            $style->setAttribute('src', 'https:' . $src);
                    }
                }
                $hide = $dom->getElementById('div_menu');
                $hide->parentNode->removeChild($hide);*/
//                $hide = $dom->getElementsByTagName('script');
//                foreach ($hide as $h)
//                    $h->parentNode->removeChild($h);
                $hide = $dom->getElementById('loading_layer');
                $hide->parentNode->removeChild($hide);
            } else {
                $temp = new \DomDocument();
                $temp->loadHTML($waybill);
                $hide = $temp->getElementById('div_menu');
                $hide->parentNode->removeChild($hide);
//                $hide = $temp->getElementsByTagName('script');
//                foreach ($hide as $h)
//                    $h->parentNode->removeChild($h);
                $hide = $temp->getElementById('loading_layer');
                $hide->parentNode->removeChild($hide);
                $form = $temp->getElementsByTagName('form');
                $node = $dom->importNode($form[0], true);
                $dom->appendChild($node);
            }

            // auto close the popup
            $xpath = new \DOMXPath($dom);
            foreach($xpath->query('//div[contains(attribute::class, "gsm_BasicLayer")]') as $e ) {
                // Delete this node
                $e->parentNode->removeChild($e);
            }
        }
        return $dom->saveHTML();
    }

    /**
     * Retrieve order's address
     *
     * @param $orders
     * @param Request $request
     * @return array|mixed
     */
    public function printAddress($orders, Request $request)
    {
        $isBulk = $request->get('is_bulk', false);

        $allOrders = [];
        // For bulk orders
        if ($isBulk) {
            foreach ($orders as $order) {
                $allOrders[] = Order::whereId($order)->first();
            }
        } else {
            // For single order
            $allOrders[] = $orders;
        }

        $documents = [];
        $region = ($this->account->region_id == Region::SINGAPORE) ? 'SG' : 'MY';
        foreach ($allOrders as $order) {
            try {
                $seller_no = $this->client->getSellerNo();
                $ids = [$order->external_id];
                $svc = $region;
                $order = 1; // number of orders

                // $svc = OrderProduct::with('order')->whereIn('id', $products->pluck('id'))->get()->map(function($values){
                //     return $values->order->payment_country;
                // })->implode(',');

                $response =  $this->client->request('post', 'GMKT.INC.Gsm.Web/Popup/Delivery/pop_PrintAddress.aspx', [
                    'form_params'   => [
                        'hid_cust_no'   => $seller_no,
                        'CustNo'        => $seller_no,
                        'countPackNo'   => $order,
                        'arrContrNo'    => implode(',', $ids),
                        'Selected'      => 'N',
                        'Type'          => 'LF', // LF - Label Print (Full Info), NF - Normal Print (Full Info)
                        'arrSvcNationCd'=> $svc,
                        'Qoo10DispAddrType' => ''
                    ]
                ]);


                if ($response->getStatusCode() === Response::HTTP_OK) {
                    $documents[] = $response->getBody();
                } else {
                    set_log_extra('response', $response);
                    throw new \Exception('Unable to retrieve airway bill for Qoo10 Legacy');
                }
            } catch (\Exception $exception) {
                return $this->respondBadRequestError($exception->getMessage());
            }
        }

        return $this->parseDocument($documents);
    }

    /**
     * Get order's shipping statement
     *
     * @param $orders
     * @param Request $request
     * @return array|mixed|string
     */
    public function shippingStatement($orders, Request $request)
    {
        $isBulk = $request->get('is_bulk', false);

        $allOrders = [];
        // For bulk orders
        if ($isBulk) {
            foreach ($orders as $order) {
                $allOrders[] = Order::whereId($order)->first();
            }
        } else {
            // For single order
            $allOrders[] = $orders;
        }

        $documents = [];
        $region = ($this->account->region_id == Region::SINGAPORE) ? 'SG' : 'MY';
        foreach ($allOrders as $order) {
            try {
                $seller_no = $this->client->getSellerNo();
                $ids = [$order->external_id];
                $svc = $region;
                $order = 1; // number of orders

                $response =  $this->client->request('post', 'GMKT.INC.Gsm.Web/Popup/Delivery/pop_PrintStatement.aspx', [
                    'form_params'   => [
                        'hid_cust_no'   => $seller_no,
                        'CustNo'        => $seller_no,
                        'countPackNo'   => $order,
                        'arrContrNo'    => implode(',', $ids),
                        'Selected'      => 'Y',
                        'Type'          => 'Delivery',
                        'arrSvcNationCd'=> $svc,
                        'rcv_info'      => 'Y',
                        'seller_info'   => 'Y',
                        'return_addr_info'  => 'Y',
                        'sub_addr_info' => 'Y'
                    ]
                ]);

                if ($response->getStatusCode() === Response::HTTP_OK) {
                    $documents[] = $response->getBody();
                } else {
                    set_log_extra('response', $response);
                    throw new \Exception('Unable to retrieve airway bill for Qoo10 Legacy');
                }
            } catch (\Exception $exception) {
                return $this->respondBadRequestError($exception->getMessage());
            }
        }

        return $this->parseDocument($documents);
    }

    /**
     * Parse address document
     *
     * @param $documents
     * @return array|string
     */
    public function parseDocument($documents)
    {
        if (empty($documents)) {
            return [];
        }

        $addresses = '';
        foreach ($documents as $document) {
            $addresses .= '<div style="page-break-before: always;">'.$document.'</div>';
        }
        return $addresses.'<script type="text/javascript">window.print();</script>';
    }

    /**
     * Retrieve Seller Number
     *
     * @return mixed|null
     * @throws
     */
    protected function getSellerNo()
    {
        $sellerNo = $this->client->getSellerNo();
        if (!isset($sellerNo) || is_null($sellerNo)) {
            $this->client->login();
            $sellerNo = $this->client->getSellerNo();
            if (!isset($sellerNo)) {
                return null;
            }
        }
        return $sellerNo;
    }

    /**
     * Retrieves all the cancellation reasons for the order
     *
     * @return mixed
     * @throws \Exception
     */
    public function reasons()
    {
        return [
            'NR' => 'Out of Stock',
            'DD' => 'Shipping Delay',
            'NC' => 'Customers\' Request',
            'BG' => 'Change Options',
            'UD' => 'Undeliverable Region',
        ];
    }

    /**
     * Cancel order
     *
     * @param Order $order
     * @param Request $request
     * @return bool|mixed
     * @throws \Exception
     */
    public function cancel(Order $order, Request $request)
    {
        // Cancel reason is required
        if (!$reason = $request->input('reason')) {
            return $this->respondBadRequestError('You need to specify the reason.');
        }

        $cache = substr(str_replace(".", "", microtime(true)), 0, -1);
        $region = ($this->account->region_id == Region::SINGAPORE) ? 'SG' : 'MY';

        $response =  $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_ClaimBizService.asmx/SetCancelConfirm', [
            'json' => [
                'sell_cust_no' => $this->client->getSellerNo(),
                'contr_no' => $order->external_id,
                'pack_no' => $order->data['pack_no'],
                'claim_no' => '0',
                'claim_why' => $reason,
                'claim_rec' => '',
                'claim_confirm_rec' => $request->input('memo'),
                'reg_id'=> $this->client->getSellerNo(),
                'claim_where' => 'G',
                'out_of_stock_option' => '',
                'svc_nation_cd' => $region,
                '___cache_expire___' => $cache
            ]
        ]);

        $response = json_decode($response->getBody(), true);

        if (!isset($response['d']) || $response['d']['returnCode'] != 0) {
            if (isset($response['d']['returnMessage'])) {
                set_log_extra('response', $response);
                return $this->respondBadRequestError($response['d']['returnMessage']);
            } else {
                set_log_extra('response', $response);
                throw new \Exception('Unable to cancel order for Qoo10 Legacy');
            }
        }

        $this->get($order->external_id, ['deduct' => false]);

        return true;
    }

    /*************************************** Fulfillment ***************************************/

    /**
     * Two methods of fulfillment based on order shipment providers
     * Seller Delivery
     * - 2 steps - update estimated shipping date > update shipping status
     * contains updateEstimatedShippingDate, getDeliveryCompanyList, updateShippingStatus
     *
     * Qxpress / Qprime
     * - delivery pickup
     * - update pickup > update shipping status
     */

    /**
     * Seller Delivery
     * Update Estimated Shipping Date
     *
     * QSM - Shipping & Claim > Shipping > Update Estimated Shipping Date
     *
     * @param Order $order
     * @param Request $request
     * @return bool|mixed
     * @throws \Exception
     */
    public function updateEstimatedShippingDate(Order $order, Request $request)
    {
        // Estimated date is required
        if (!$estimatedDate = $request->input('estimated_date')) {
            return $this->respondBadRequestError('You need to specify the estimated date.');
        }
        // Estimated date is required
        if (!$delayReason = $request->input('delay_reason')) {
            return $this->respondBadRequestError('You need to specify a delay reason.');
        }

        $region = ($this->account->region_id == Region::SINGAPORE) ? 'SG' : 'MY';
        $response =  $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_DeliveryBizService.asmx/SetSendPlanDT', [
            'json' => [
                'contr_no' => $order->external_id,
                'send_plan_dt' => Carbon::parse($estimatedDate)->setTimezone('Asia/Singapore')->format("M d, Y"),
                'send_plan_rec' => $delayReason,
                'send_plan_desc' => $request->input('delay_reason_description'),
                'svc_nation_cd' => $region,
                'login_id' => $this->client->getSellerNo() // if fail, change this to name of profile
            ]
        ]);

        $response = json_decode($response->getBody(), true);

        if (!isset($response['d'])) {
            set_log_extra('response', $response);
            throw new \Exception('Unable to update estimated date for Qoo10 Legacy');
        } else if (!isset($response['d']['returnCode']) || $response['d']['returnCode'] != 0) {
            if (isset($response['d']['Rows'][0]['retcode']) && $response['d']['Rows'][0]['retcode'] == '-10') {
                return $this->respondBadRequestError('Error order cannot update estimated date again.');
            } else if (isset($response['d']['returnMessage'])) {
                set_log_extra('response', $response);
                return $this->respondBadRequestError($response['d']['returnMessage']);
            } else {
                set_log_extra('response', $response);
                throw new \Exception('Unable to update estimated date for Qoo10 Legacy');
            }
        }

        // Update order to RTS
        $order->fulfillment_status = FulfillmentStatus::READY_TO_SHIP();
        $order->save();

        $order->items()->update([
            'fulfillment_status' => FulfillmentStatus::READY_TO_SHIP()
        ]);

        return true;
    }

    /**
     * Seller Delivery
     * Get delivery company list, used when update shipping status
     *
     * @return mixed
     * @throws \Exception
     */
    public function getDeliveryCompanyList()
    {
        $region = ($this->account->region_id == Region::SINGAPORE) ? 'SG' : 'MY';
        $response = $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_DeliveryBizService.asmx/GetTranscName', [
            'json' => [
                'svc_nation_cd' => $region
            ]
        ]);

        $response = json_decode($response->getBody(), true);

        if(isset($response['d'])) {
            return $response['d']['Rows'];
        } else {
            if (isset($response['d']['returnMessage'])) {
                set_log_extra('response', $response);
                return $this->respondBadRequestError($response['d']['returnMessage']);
            } else {
                set_log_extra('response', $response);
                throw new \Exception('Unable to get delivery company list for Qoo10 Legacy');
            }
        }
    }

    /**
     * Get order info for logistics
     * Qxpress & Qprime - Pickup - Update Status
     *
     * @param $order
     * @return array
     */
    public function getLogistic($order)
    {
        foreach ($order->items as $item) {
            if ($item->fulfillment_status === FulfillmentStatus::PROCESSING()->getValue() && ($item->shipment_provider === 'Qxpress' || $item->shipment_provider === 'Qprime')) {
                return [
                    'type' => 'pickup',
                    'shipment_provider' => $item->shipment_provider,
                    'data' => $this->generatePickupInfo()
                ];
            }
        }
        return [
            'type' => ''
        ];
    }

    /**
     * Generate parameter required for pickup
     * work day, pickup info, pickup address
     *
     * @throws \Exception
     */
    public function generatePickupInfo()
    {
        $workDayList = $this->getWorkDayList();

        if (isset($workDayList['d']['Rows'])) {
            $workDayList = collect($workDayList['d']['Rows']);
        } else {
            set_log_extra('workDayList', $workDayList);
            set_log_extra('account', $this->account);
            throw new \Exception('Unable to generate Qoo10 Pickup Info.');
        }

        $pickupInfo = $this->getPickupInfo([
            'startDate' => $workDayList->first()['work_date'],
            'endDate' => $workDayList->last()['work_date']
        ]);

        if (isset($pickupInfo['d']['Rows'])) {
            $pickupInfo = collect($pickupInfo['d']['Rows']);
        }

        $workDayList = $workDayList->map(function ($object) use ($pickupInfo) {
            $object['pickup'] = array_values($pickupInfo->where('request_date', $object['work_date'])->toArray());
            return $object;
        });

        $sellerPickupAddress = $this->getSellerPickupAddress();

        return [
            'workDay' => $workDayList,
            'pickupAddr' => $sellerPickupAddress
        ];
    }

    /**
     * Get work day list for order pickup
     * Only able to set pickup based on the days provided
     *
     */
    private function getWorkDayList()
    {
        $cache = substr(str_replace(".", "", microtime(true)),0,-1);
        $region = ($this->account->region_id == Region::SINGAPORE) ? 'SG' : 'MY';

        $response =  $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_GiosisShippingBizService.asmx/GetWorkDayList', [
            'json' => [
                'svc_nation_cd'=> $region,
                '___cache_expire___' => $cache
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get pickup info, the pickup which was created
     * Can either add number of parcel to the pickup if exists, or create a new pickup
     * pickup_stat - P1 : Queue, PX : Cancel, P3 : Done
     *
     * @param $parameter
     * @return mixed
     */
    private function getPickupInfo($parameter)
    {
        $cache      = substr(str_replace(".", "", microtime(true)),0,-1);
        $region = ($this->account->region_id == Region::SINGAPORE) ? 'SG' : 'MY';
        $response =  $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_GiosisShippingBizService.asmx/GetPickupinfo', [
            'json' => [
                'cust_no' => $this->client->getSellerNo(),
                'date_type'=> 'REQ',
                'e_date'=> $parameter['endDate'],
                'pickup_stat'=> 'P1',
                's_date'=> $parameter['startDate'],
                'svc_nation_cd'=> $region,
                '___cache_expire___' => $cache
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get Seller Pickup Address
     *
     * cust_no - seller no
     */
    public function getSellerPickupAddress()
    {
        $cache      = Carbon::now()->setTimezone('Asia/Singapore')->format('D M d Y H:i:s GMT+0800 (+08)');

        $response = $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_SellerBizService.asmx/GetSellerPickupAddr', [
            'json'  => json_decode('{"cust_no":"'.$this->client->getSellerNo().'","addr_type":"1","___cache_expire___":"'.$cache.'"}', true)
        ]);

        $json = json_decode($response->getBody(), true);

        return isset($json['d']) ? $json['d']['Rows'] : $json;
    }

    /**
     * Order pickup create/update
     *
     * @param Order $order
     * @param Request $request
     * @return bool|mixed
     */
    public function pickup(Order $order, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'quantity' => 'required',
            'request_date' => 'required',
            'pickup_address_no' => 'required',
        ]);

        if ($validator->fails()){
            return $this->showValidationError($validator);
        }

        // Create/Update pickup
        $cache = substr(str_replace(".", "", microtime(true)), 0, -1);
        $region = ($this->account->region_id == Region::SINGAPORE) ? 'SG' : 'MY';

        if ($request->get('type') == 'new') {
            $type = 'I';
        } else if ($request->get('type') == 'edit') {
            $type = 'U';
        } else {
            return $this->respondBadRequestError('Invalid of type.');
        }

        $resp = $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_GiosisShippingBizService.asmx/SetPickupInfoBySeller', [
            'json' => [
                'work_type' => $type, // I request, D cancel, U edit
                'seqno' => $request->get('id') ?? 0, // edit required the id
                'cnt' => $request->get('quantity') ?? 1, // parcel quantity
                'request_dt' => $request->get('request_date'),
                'request_time' => '09:00-17:00',
                'cust_no' => $this->client->getSellerNo(),
                'reg_id' => $this->client->getSellerNo(), //Name
                'svc_nation_cd' => $region,
                'memo' => $request->get('memo') ?? '',
                'pickup_addr_no' => $request->get('pickup_address_no') ?? '',
                'hp_no' => $request->get('mobile_no') ?? '',
                '___cache_expire___' => $cache
            ]
        ]);

        $response = json_decode($resp->getBody(), true);

        $pickupId = null;
        if (isset($response['d'])) {
            if (is_array($response['d'])) {
                if ($response['d']['Rows'][0]['ret_code'] == -40) {
                    return $this->respondBadRequestError('Error creating pickup. Selected address might not be available for the pick up service');
                } else if ($response['d']['Rows'][0]['ret_code'] == -35) {
                    // -35 - pickup progress on request, please wait
                    return $this->respondBadRequestError('Pickup request in progress. Please try again after 3 minute.');
                } else if ($response['d']['Rows'][0]['ret_code'] == -45) {
                    return $this->respondBadRequestError('Pickup request for the same date already exists.');
                } else if ($response['d']['Rows'][0]['ret_code'] == -10) {
                    return $this->respondBadRequestError('Sorry, selected address is not available for Qxpress pick-up service.');
                } else if ($response['d']['Rows'][0]['ret_code'] < 0) {
                    $pickupId = null;
                } else {
                    return true;
                }
            }
        }

        if (!$pickupId) {
            return $this->respondBadRequestError('Error creating pickup. Please try again.');
        }
        return true;
    }

    /**
     * Seller Delivery
     *
     * @param Order $order
     * @param Request $request
     * @return bool|mixed
     */
    public function fulfillment(Order $order, Request $request)
    {
        // @NOTE - transc_cd and takbae_nm gotten from getDeliveryCompanyList. Can change the property name to a readable name instead
        // $request->input('transc_cd');
        // $request->input('takbae_nm');
        // $request->input('songjang_no'); // tracking number fill by user

        if (!$request->input('transc_cd') || !$request->input('takbae_nm')) {
            return $this->respondBadRequestError('You need to select delivery company.');
        }

        $region = ($this->account->region_id == Region::SINGAPORE) ? 'SG' : 'MY';

        $response = $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_DeliveryBizService.asmx/SendingConfirm', [
            'json' => [
                'gubun'         => 'ADD',
                'contr_no'      => $order->external_id,
                'sending_dt'    => Carbon::now()->format("M d, Y"),
                'transc_cd'     => $request->input('transc_cd'),
                'takbae_nm'     => $request->input('takbae_nm'),
                'songjang_no'   => $request->input('songjang_no'),
                'rcv_nm'        => ($order->shipping_address['name']) ?? '',
                'confirm_id'    => 'PMG2019',
                'sell_cust_no'  => $this->client->getSellerNo(),
                'svc_nation_cd' => $region,
                'chg_gubun'     => 'D3_orderNo',
                '___cache_expire___'    => Carbon::now()->setTimezone('Asia/Singapore')->format('D M d Y H:i:s GMT+0800 (+08)')
            ]
        ]);

        $response = json_decode($response->getBody(), true);

        if (isset($response['d']) && isset($response['d']['Rows'][0]['retcode'])) {
            if ($response['d']['Rows'][0]['retcode'] == '0') {
                return true;
            } else if ($response['d']['Rows'][0]['retcode'] == '-130') {
                return $this->respondBadRequestError('Error shipping order with selected delivery company.');
            } elseif ($response['d']['Rows'][0]['retcode'] == '-22') {
                return $this->respondBadRequestError('Invalid size of tracking number. (tracking number size must be match with selected delivery company standard)');
            } elseif ($response['d']['Rows'][0]['retcode'] == '-23') {
                return $this->respondBadRequestError('Invalid tracking number. Please enter again.');
            } else {
                return $this->respondBadRequestError('Unable to update shipping status for Qoo10 Legacy.');
            }
        } else {
            return $this->respondBadRequestError('Unable to update shipping status for Qoo10 Legacy.');
        }
    }
}
