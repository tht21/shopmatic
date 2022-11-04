@extends('admin.layouts.app')

@section('content')
    <admin-create-ticket-component :keys="['ticket_categories_id', 'subject', 'description', 'attachments']"
                                   :request_url="'{{ route('web.tickets.store') }}'"
                                   :title="'{{ __('Create Ticket') }}'"
    >
    </admin-create-ticket-component>
@endsection
