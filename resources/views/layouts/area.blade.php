@extends('layouts.base')
@section('noindex', '1')

@section('body')
<header class="ef-header">
    <div class="ef-header__in">
        <a class="ef-logo" href="{{ route(auth()->user()->homeRoute()) }}"><img src="{{ asset('img/logo-effepi.png') }}" alt="Effepi Telefonia" width="520" height="222"></a>
        <nav class="ef-nav" aria-label="Menu area riservata">
            @yield('nav')
            <span class="ef-muted ef-nav__opt">{{ auth()->user()->name }}</span>
            <form class="ef-inline-form" method="post" action="{{ route('logout') }}">@csrf
                <button class="ef-btn ef-btn--ghost ef-btn--small" type="submit">Esci</button>
            </form>
        </nav>
    </div>
</header>
<main class="ef-area">
    <div class="ef-wrap">
        @include('partials.flash')
        @yield('content')
    </div>
</main>
@include('partials.footer')
@endsection
