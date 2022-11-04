@extends('admin.layouts.app')

@section('content')
    <admin-show-ticket-component :index_url="'{{ route('web.tickets.index') }}'"
                                 :request_url="'{{ route('web.tickets.show', $ticket->case_id) }}'"
                                 :status_array="{{ json_encode($status_array) }}"
    >
    </admin-show-ticket-component>
@endsection
