@extends('layouts.base')

@section('body')
<div class="ef-topbar"><div class="ef-topbar__in">
    <a href="{{ route('home') }}#contatti">Contatti</a>
    @auth
        <a href="{{ route(auth()->user()->homeRoute()) }}">Area riservata</a>
    @else
        <a href="{{ route('login') }}">Accedi all'area riservata</a>
    @endauth
</div></div>
<header class="ef-header">
    <div class="ef-header__in">
        <a class="ef-logo" href="{{ route('home') }}"><img src="{{ asset('img/logo-effepi.png') }}" alt="Effepi Telefonia, Master Dealer Centro Sud Italia" width="520" height="222"></a>
        <nav class="ef-nav ef-nav--links" id="ef-menu" aria-label="Menu principale">
            <a class="ef-nav__opt" href="{{ route('home') }}#chi-siamo">Chi siamo</a>
            <a class="ef-nav__opt" href="{{ route('home') }}#per-i-negozi">Per i negozi</a>
            <a class="ef-nav__opt" href="{{ route('home') }}#diventa-agente">Diventa agente</a>
            <a class="ef-nav__opt" href="{{ route('home') }}#contatti">Contatti</a>
        </nav>
        <a class="ef-btn ef-btn--small ef-btn--accent ef-header__cta" href="{{ route('home') }}#registrati">Registrati</a>
        <button class="ef-burger" type="button" aria-expanded="false" aria-controls="ef-menu" aria-label="Apri il menu"><span></span><span></span><span></span></button>
    </div>
</header>
<main>@yield('content')</main>
@include('partials.footer')
@endsection
