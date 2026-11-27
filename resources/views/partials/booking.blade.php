{{-- Prenotazione della videocall: usata da entrambi i moduli della home. $bag = dealer | agent --}}
<fieldset class="ef-fieldset">
    <legend>Prenota la tua videocall di presentazione</legend>
    <p class="ef-muted">Su Google Meet, circa 30 minuti. Disponibile lunedì, mercoledì e venerdì, dalle 14 alle 19.</p>
    <div class="ef-cols2">
        <div class="ef-field">
            <label for="{{ $bag }}-date">Data</label>
            <input class="ef-input" type="date" id="{{ $bag }}-date" name="meeting_date" required
                   min="{{ now()->toDateString() }}" max="{{ now()->addDays(config('effepi.videocall.max_days_ahead'))->toDateString() }}"
                   value="{{ $active ? old('meeting_date') : '' }}">
            @error('meeting_date', $bag)<div class="ef-error">{{ $message }}</div>@enderror
        </div>
        <div class="ef-field">
            <label for="{{ $bag }}-time">Ora</label>
            <select class="ef-input" id="{{ $bag }}-time" name="meeting_time" required data-old="{{ $active ? old('meeting_time') : '' }}">
                <option value="">Seleziona</option>
            </select>
            @error('meeting_time', $bag)<div class="ef-error">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="ef-muted" data-booking-hint aria-live="polite">Scegli prima la regione e la data.</div>
</fieldset>
<div class="ef-hp" aria-hidden="true">
    <label>Lascia vuoto questo campo <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
</div>
<label class="ef-check">
    <input type="checkbox" name="privacy" value="1" required>
    <span>Ho letto l'<a href="{{ route('privacy') }}" target="_blank" rel="noopener">informativa privacy</a> e acconsento a essere ricontattato.</span>
</label>
@error('privacy', $bag)<div class="ef-error">{{ $message }}</div>@enderror
<button class="ef-btn" type="submit">Invia la registrazione</button>
