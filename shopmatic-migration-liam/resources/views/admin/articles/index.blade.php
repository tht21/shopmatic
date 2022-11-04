@extends('admin.layouts.app')

@section('header-right')
    <a href="{{ route('admin.articles.create') }}" class="btn btn-sm btn-neutral"><i class="fas fa-plus"></i> &nbsp; {{ __('Create Article') }} </a>
    <button type="button" class="btn btn-sm btn-neutral">Filter</button>
@endsection

@section('content')
    <index-article-component :index_url="'{{ route('admin.articles.index') }}'" :request_url="'{{ route('web.articles.index') }}'" :title="'{{ __('All Articles') }}'"></index-article-component>
@endsection
