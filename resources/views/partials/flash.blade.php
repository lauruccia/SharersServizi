@if (session('status'))
    <div class="ef-alert" role="status">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="ef-alert ef-alert--error" role="alert">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif
