@extends('articles.layouts.app')

@section('content')
    <div class="col-lg-10">
        <p class="my-2">
            {{ $article->outline }}
        </p>
        <hr>

        @php

            $dom = new DOMDocument();
            $dom->loadHTML($article->description);

            $xpath = new DOMXPath($dom);
            $heads = $xpath->query('//h1|//h2|//h3|//h4|//h5|//h6');
        @endphp

        @for ($i = 0; $i < $heads->length; $i++)
            @php
                $head = $heads->item($i);
                $head->setAttribute("id", Str::slug($head->nodeValue));
            @endphp
        @endfor

        @php $description = $dom->saveHTML(); @endphp


        <div class="p-2">
            {!! $description !!}
        </div>
    </div>
@endsection