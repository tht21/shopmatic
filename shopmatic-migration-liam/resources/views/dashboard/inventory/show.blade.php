@extends('dashboard.layouts.app')

@section('content')
    <div class="card">
        <!-- Card header -->
        <div class="card-header border-0">
            <div>
                <h1 class="font-weight-light">Managing Inventory for {{ $inventory->sku }}</h1>
            </div>
        </div>
        <div class="card-body">
            <inventory-detail-component :inventory="{{ json_encode($inventory) }}"></inventory-detail-component>
        </div>
    </div>
@endsection
