@extends('dashboard.layouts.app')

@section('content')
    <index-component :title="'{{ __('Past Imports') }}'"
                     :request_url="'{{ route('web.products.import.tasks') }}'"
                     :fields="{{ $fields }}"
                     :headers="{{ $headers }}">
    </index-component>
@endsection