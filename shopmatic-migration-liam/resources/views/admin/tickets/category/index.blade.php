@extends('admin.layouts.app')

@section('header-right')
    <button type="button" class="btn btn-sm btn-neutral" data-toggle="modal" data-target="#create-category-form"><i class="fas fa-plus"></i> &nbsp; Create</button>
    <button type="button" class="btn btn-sm btn-neutral">Filter</button>
@endsection

@section('content')
    <index-ticket-category-component
        :title="'{{ __('Ticket Categories') }}'"
        :request_url="'{{ route('web.tickets.category.index') }}'"
        :fields="{{ $fields }}"
        :headers="{{ $headers }}"
    >
    </index-ticket-category-component>

    <create-ticket-category-component :title="'{{ __('Create Categories') }}'"
                               :request_url="'{{ route('web.tickets.category.index') }}'"
    >
    </create-ticket-category-component>
@endsection

@section('script')

@endsection

