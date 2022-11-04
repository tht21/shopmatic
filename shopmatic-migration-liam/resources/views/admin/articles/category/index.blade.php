@extends('admin.layouts.app')

@section('header-right')
    <button type="button" class="btn btn-sm btn-neutral" data-toggle="modal" data-target="#create-category-form"><i class="fas fa-plus"></i> &nbsp; Create</button>
    <button type="button" class="btn btn-sm btn-neutral">Filter</button>
@endsection

@section('content')
    <index-article-category-component
        :title="'{{ __('Article Categories') }}'"
        :request_url="'{{ route('web.articles.category.index') }}'"
        :fields="{{ $fields }}"
        :headers="{{ $headers }}"
    >
    </index-article-category-component>

    <create-article-category-component :title="'{{ __('Create Categories') }}'"
                               :index_url="'{{ route('web.articles.category.index') }}'"
                               :request_url="'{{ route('web.articles.category.store') }}'"
    >
    </create-article-category-component>
@endsection

@section('script')

@endsection

