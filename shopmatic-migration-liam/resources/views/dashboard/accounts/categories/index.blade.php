@extends('dashboard.layouts.app')

@section('header-right')
	<account-category-import-component :global="global"></account-category-import-component>
    <a class="btn btn-sm btn-neutral" href="{{ route('dashboard.account.categories.create') }}">{{ __('field.add_new') }}</a>
@endsection

@section('content')
    <account-category-component :request_url="'{{ route('web.accounts.index') }}?feature=account_categories&limit=100'" :global="global" @update="updateGlobal"></account-category-component>
@endsection
