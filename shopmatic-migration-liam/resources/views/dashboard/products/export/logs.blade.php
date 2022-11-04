@extends('dashboard.layouts.app')

@section('header-right')
    <b-btn size="sm" href="{{ route('dashboard.products.export') }}">Back</b-btn>

@endsection

@section('content')
    <index-component :title="'{{ __('Past Exports') }}'"
                     :request_url="'{{ route('web.products.export.tasks') }}?type={{ request()->get('type') }}'"
                     :fields="{{ $fields }}"
                     :headers="{{ $headers }}">
    </index-component>
@endsection
