@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => config('app.url')])
            <table align="center">
                <tr>
                    <td>
                        <img src="{{ url('images/logo-white.png') }}" class="header-logo" alt="Combinesell Logo">
                    </td>
                </tr>
                <tr>
                    <td>
                        <h4 style="color: white; font-weight: lighter">
                            Singapore #1 multichannel e-commerce solution.
                        </h4>
                    </td>
                </tr>
            </table>
        @endcomponent
    @endslot

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        @slot('subcopy')
            @component('mail::subcopy')
                {{ $subcopy }}
            @endcomponent
        @endslot
    @endisset

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            © {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
        @endcomponent
    @endslot
@endcomponent
