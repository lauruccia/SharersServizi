<nav class="ef-tabs" aria-label="Sezioni">
    @foreach ($tabs as $key => $label)
        <a class="ef-tab" href="{{ route($route, ['scheda' => $key]) }}" @if ($tab === $key) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
