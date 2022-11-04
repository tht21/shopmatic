@extends('dashboard.layouts.app')

@section('content')
    <div class="card">
        <!-- Card header -->
        <div class="card-header border-0">
            <div>
                <h1 class="font-weight-light">Order {{ $order->external_id }} <span class="px-3 badge badge-lg badge-{{ $order->getStatusTextColor() }}">{{ $order->fulfillment_status_text }}</span></h1>
                <span class="d-inline-block pr-3"><i class="far fa-clock mr-1"></i> Made at {{ $order->order_placed_at }}</span> |
                <span class="d-inline-block px-3"><i class="far fa-edit mr-1"></i> Updated at {{ $order->order_updated_at }}</span> |
                <span class="d-inline-block px-3"><i class="fas fa-link mr-1"></i> {{ $order->external_source }}
                    @if ($order->account)
                        <span>({{ $order->account->name }})</span>
                    @endif
                </span>
            </div>
        </div>
        <div class="card-body">
            <order-detail-component :order="{{ json_encode($order) }}"></order-detail-component>
        </div>
    </div>
@endsection
