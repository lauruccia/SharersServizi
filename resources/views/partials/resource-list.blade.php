@if ($items->isEmpty())
    <p class="ef-muted">In caricamento.</p>
@else
    <ul class="ef-list">
        @foreach ($items as $r)
            <li><a class="ef-link" href="{{ $r->href() }}" target="_blank" rel="noopener">{{ $r->title }}</a>@if ($r->description)<br><span class="ef-muted">{{ $r->description }}</span>@endif</li>
        @endforeach
    </ul>
@endif
