@if ($user && auth()->user()->canManageUser($user))
<details class="ef-details">
    <summary>Accesso</summary>
    <form method="post" action="{{ route('manager.network.password', $user) }}" style="margin-bottom:8px">@csrf
        <button class="ef-btn ef-btn--small ef-btn--ghost" type="submit">Invia nuova password provvisoria</button>
    </form>
    <form method="post" action="{{ route('manager.network.toggle', $user) }}">@csrf
        <button class="ef-btn ef-btn--small ef-btn--danger" type="submit">{{ $user->active ? 'Sospendi accesso' : 'Riattiva accesso' }}</button>
    </form>
</details>
@endif
