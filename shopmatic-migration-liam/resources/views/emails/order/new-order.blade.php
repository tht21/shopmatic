@component('mail::message')
Hello,
    
You have new order #**{{ $order->external_order_number }}** from account {{ $order->account->name }} ({{ $order->account->integration->name }}).

@component('mail::button', ['url' => url('/dashboard/orders/' . $order->id)])
View Order
@endcomponent

@endcomponent
