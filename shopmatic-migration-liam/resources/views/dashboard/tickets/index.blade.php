@extends('dashboard.layouts.app')

@section('header-right')
    <a href="{{ route('dashboard.tickets.create') }}" class="btn btn-sm btn-neutral"><i class="fas fa-plus"></i> &nbsp; Create Ticket </a>
    <button type="button" class="btn btn-sm btn-neutral">Filter</button>
@endsection

@section('content')

    <user-index-ticket-component
        :title="'{{ __('All Tickets') }}'"
        :request_url="'{{ route('web.tickets.index') }}'"
        :keys="['ticket_categories_id', 'related_id', 'related_type', 'subject', 'description']">
    </user-index-ticket-component>

@endsection
