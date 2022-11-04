@extends('dashboard.layouts.app')

@section('content')
    <index-retail-component :integrations="{{ $integrations }}"></index-retail-component>
@endsection

