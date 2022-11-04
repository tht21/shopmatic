@extends('dashboard.layouts.app')

@section('header-right')
    <export-sales-component :global="global"></export-sales-component>
@endsection

@section('content')
    <index-sales-component :integrations="{{ json_encode($integrations) }}" :global="global" @update="updateGlobal"></index-sales-component>
@endsection
