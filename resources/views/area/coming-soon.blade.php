@extends('layouts.area')
@section('title', $title)

@section('content')
<p><a class="ef-link" href="{{ route(auth()->user()->homeRoute()) }}">← Torna all'area riservata</a></p>
<h1>{{ $title }}</h1>
<div class="ef-empty">Questa funzione è in preparazione e sarà disponibile a breve.</div>
@endsection
