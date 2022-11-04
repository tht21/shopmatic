@extends('layouts.app')

@section('content')
<div>
    <div class="header bg-gradient-primary py-7 py-lg-8 pt-lg-9">
        <div class="container">
            <div class="header-body text-center mb-4">
                <div class="row justify-content-center">
                    <div class="px-5">
                        <h1 class="text-white"><strong>Welcome!</strong> How can we help?</h1>
                    </div>
                </div>
            </div>
        </div>

        <header-knowledgebase-component></header-knowledgebase-component>

        <div class="separator separator-bottom separator-skew zindex-100">
            <svg x="0" y="0" viewBox="0 0 2560 100" preserveAspectRatio="none" version="1.1" xmlns="http://www.w3.org/2000/svg">
                <polygon class="fill-white" points="2560 0 2560 100 0 100"></polygon>
            </svg>
        </div>
    </div>

    <index-knowledgebase-component></index-knowledgebase-component>
</div>
@endsection

@section('footer')
    <script>
        $(document).ready(function(){
            $("body").addClass( "bg-white" );
        });
    </script>
@endsection