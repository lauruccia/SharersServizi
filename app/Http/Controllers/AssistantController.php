<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Services\FaqAssistant;
use Illuminate\Http\Request;

/** Domande e risposte: elenco delle FAQ e assistente che cerca la risposta giusta. */
class AssistantController extends Controller
{
    public function __invoke(Request $request, FaqAssistant $assistant)
    {
        $q = trim((string) $request->query('q', ''));
        abort_if(mb_strlen($q) > 200, 422);

        return view('assistant', [
            'q' => $q,
            'answers' => $q !== '' ? $assistant->answer($q) : collect(),
            'faqs' => Faq::query()->where('active', true)->orderBy('position')->orderBy('id')->get(),
        ]);
    }
}
