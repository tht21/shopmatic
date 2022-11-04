@extends('admin.layouts.app')

@section('content')
    <create-article-component :index_url="'{{ route('web.articles.index') }}'"
                              :request_url="'{{ route('admin.articles.create') }}'">
    </create-article-component>
@endsection

