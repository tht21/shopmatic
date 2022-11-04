@extends('dashboard.layouts.app')

@section('content')
    <user-show-ticket-component :index_url="'{{ route('web.tickets.index') }}'" :request_url="'{{ route('web.tickets.show', $ticket->case_id) }}'"></user-show-ticket-component>
@endsection
