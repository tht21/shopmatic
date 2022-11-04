@component('mail::message')
Hello,

Your inventory <b>{{ $inventory->sku }}</b> is low on stock. The current stock is **{{ $stock }}**.

The last change is from: {{ $log->message }}

We will no longer notify you regarding this inventory unless it is out of stock and if you have the notification enabled for it.

@component('mail::button', ['url' => url('/dashboard/inventory/' . $inventory->id)])
View Inventory
@endcomponent

@endcomponent
