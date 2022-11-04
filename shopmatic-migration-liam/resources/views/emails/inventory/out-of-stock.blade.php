@component('mail::message')
Hello,

Your inventory **{{ $inventory->sku }}** is **out of stock**!
    
The last change is from: {{ $log->message }}

@component('mail::button', ['url' => url('/dashboard/inventory/' . $inventory->id)])
View Inventory
@endcomponent

@endcomponent
