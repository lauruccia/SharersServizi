<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Assistente virtuale: oggi cerca nelle FAQ di Effepi, domani potrà
 * appoggiarsi a un'API di IA mantenendo la stessa interfaccia.
 */
interface FaqAssistant
{
    /** @return Collection<int, \App\Models\Faq> risposte pertinenti, dalla più adatta */
    public function answer(string $question): Collection;
}
