@extends('admin.layouts.app')

@section('content')
    <edit-article-component :index_url="'{{ route('web.articles.index') }}'"
                            :request_url="'{{ route('web.articles.update', 'article') }}'"
                            :id="{{ json_encode($article->id) }}"
    ></edit-article-component>
@endsection
