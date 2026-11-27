@if (! $user)
    <span class="ef-badge">senza accesso</span>
@else
    <span class="ef-muted">{{ $user->username }}</span><br>
    @if (! $user->active)<span class="ef-badge ef-badge--warn">sospeso</span>
    @elseif ($user->must_change_password)<span class="ef-badge ef-badge--warn">primo accesso da fare</span>
    @else<span class="ef-badge ef-badge--ok">attivo</span>@endif
@endif
