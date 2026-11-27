<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KeywordFaqAssistant implements FaqAssistant
{
    private const STOPWORDS = ['come', 'dove', 'quando', 'quale', 'quali', 'cosa', 'che', 'per', 'con', 'una', 'uno', 'gli', 'dei', 'del', 'della', 'delle', 'nel', 'nella', 'sono', 'posso', 'devo', 'fare', 'the', 'alla', 'allo', 'sul', 'sulla', 'mio', 'mia', 'miei', 'non', 'piu', 'anche'];

    public function answer(string $question): Collection
    {
        $tokens = $this->tokens($question);
        if ($tokens === []) {
            return collect();
        }

        return Faq::query()->where('active', true)->get()
            ->map(function (Faq $faq) use ($tokens) {
                $title = $this->normalise($faq->question.' '.$faq->keywords);
                $body = $this->normalise($faq->answer);
                $score = 0;
                foreach ($tokens as $t) {
                    $score += (str_contains($title, $t) ? 3 : 0) + (str_contains($body, $t) ? 1 : 0);
                }
                $faq->score = $score;

                return $faq;
            })
            ->filter(fn (Faq $f) => $f->score > 0)
            ->sortByDesc('score')
            ->take(3)
            ->values();
    }

    /** @return list<string> */
    private function tokens(string $text): array
    {
        $words = preg_split('/[^a-z0-9]+/', $this->normalise($text), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter($words, fn ($w) => strlen($w) >= 3 && ! in_array($w, self::STOPWORDS, true))));
    }

    private function normalise(string $text): string
    {
        return Str::lower(Str::ascii($text));
    }
}
