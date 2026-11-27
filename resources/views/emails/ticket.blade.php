@extends('emails.layout')
@section('content')
@if ($toStaff)
<p><strong>{{ $message->user?->name }}</strong> ({{ $ticket->categoryLabel() }}@if ($ticket->dealer) · {{ $ticket->dealer->business_name }}@endif) ha scritto:</p>
@else
<p>Il back office ha risposto alla tua segnalazione:</p>
@endif
<p style="white-space:pre-line;border-left:4px solid #dfe2e8;padding-left:12px">{{ $message->body }}</p>
<p><a href="{{ route('tickets.show', $ticket) }}" style="display:inline-block;padding:12px 22px;background:#98c23a;color:#ffffff;text-decoration:none;border-radius:30px;font-weight:bold">Apri la segnalazione</a></p>
@endsection
