@extends('layouts.base')
@section('noindex', '1')

@section('body')
<main class="ef-section">
    <div class="ef-wrap ef-narrow">
        <a class="ef-logo" href="{{ route('home') }}"><img src="{{ asset('img/logo-effepi.png') }}" alt="Effepi Telefonia" width="520" height="222" style="height:70px;margin-bottom:28px"></a>
        @include('partials.flash')
        @yield('content')
    </div>
</main>
@endsection
