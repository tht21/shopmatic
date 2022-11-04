@extends('dashboard.layouts.app')

@section('content')
    <user-create-ticket-component
        :title="'{{ __('Create Ticket') }}'"
        :keys="['category', 'subject', 'description', 'related_type', 'related_id']"
        :request_url="'{{ route('web.tickets.index') }}'">
    </user-create-ticket-component>
@endsection
