@extends('admin.layouts.app')

@section('header-right')
    <a href="{{ route('admin.tickets.create') }}" class="btn btn-sm btn-neutral"><i class="fas fa-plus"></i> &nbsp; Create Ticket </a>
    <button type="button" class="btn btn-sm btn-neutral">Filter</button>
@endsection

@section('content')
    <div class="col-md-12">
        <div class="card">
            <admin-index-ticket-component :request_url="'{{ route('web.tickets.index') }}'"
                                     :priority_array="{{ json_encode($priority_array) }}"
                                     :status_array="{{ json_encode($status_array) }}"
                                     :count_ticket="{{ json_encode($count_ticket) }}"
                                     :group_ticket="{{ json_encode($group_ticket) }}"
            ></admin-index-ticket-component>
        </div>
    </div>
@endsection
