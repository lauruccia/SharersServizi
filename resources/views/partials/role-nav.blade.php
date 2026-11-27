@if (auth()->user()->isBackOffice())
    @include('partials.manager-nav')
@else
    <a href="{{ route('orders.index') }}">Ordini</a>
    <a href="{{ route('production.index') }}">Produzione</a>
    <a href="{{ route('contests.index') }}">Gare</a>
@endif
