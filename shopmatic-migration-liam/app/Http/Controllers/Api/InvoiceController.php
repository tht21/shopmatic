<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }
        $this->authorize('view', $shop);

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);
        
        $results = [];
        foreach ($shop->invoices() as $key => $value) {
            $results[] = [
                'id' => $value->id,
                'account_country' => $value->account_country,
                'account_name' => $value->account_name,
                'amount_paid' => $value->amount_paid,
                'currency' => $value->currency,
                'customer_email' => $value->customer_email,
                'invoice_pdf' => $value->invoice_pdf,
                'paid' => $value->paid,
                'created' => date('j M, Y g:i A', $value->created),
                'period_end' => date('j M, Y', $value->lines->data[0]->period->end),
                'period_start' => date('j M', $value->lines->data[0]->period->start),
                'status' => $value->status,
                'description' => $value->lines->data[0]->description
            ];
        }

        return $this->respondPagination($request, $results);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($invoiceId, Request $request)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }
        $this->authorize('view', $shop);
        
        $invoice = $shop->findInvoiceOrFail($invoiceId);

        return $shop->downloadInvoice($invoiceId, [
            'vendor' => 'Combinesell',
            'product' => $invoice->lines->data[0]->plan->nickname,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
