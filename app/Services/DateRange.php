<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Periodo dei conteggi: mese, trimestre, anno o intervallo personalizzato. Il default è il mese in corso. */
class DateRange
{
    public const PERIODS = ['month' => 'Mese', 'quarter' => 'Trimestre', 'year' => 'Anno', 'custom' => 'Personalizzato'];

    public function __construct(
        public readonly string $period,
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly array $input,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $period = $request->query('period', 'month');
        $period = array_key_exists($period, self::PERIODS) ? $period : 'month';
        $now = now();

        switch ($period) {
            case 'quarter':
                $year = self::intIn($request->query('year'), 2000, 2100, $now->year);
                $q = self::intIn($request->query('quarter'), 1, 4, $now->quarter);
                $from = Carbon::create($year, ($q - 1) * 3 + 1, 1)->startOfDay();
                $to = $from->copy()->addMonths(2)->endOfMonth()->startOfDay();
                $input = ['year' => $year, 'quarter' => $q];
                break;
            case 'year':
                $year = self::intIn($request->query('year'), 2000, 2100, $now->year);
                $from = Carbon::create($year, 1, 1)->startOfDay();
                $to = Carbon::create($year, 12, 31)->startOfDay();
                $input = ['year' => $year];
                break;
            case 'custom':
                $from = self::date($request->query('from')) ?? $now->copy()->startOfMonth();
                $to = self::date($request->query('to')) ?? $now->copy()->startOfDay();
                if ($to->lt($from)) {
                    [$from, $to] = [$to, $from];
                }
                $input = ['from' => $from->toDateString(), 'to' => $to->toDateString()];
                break;
            default:
                $month = self::month($request->query('month')) ?? $now->copy()->startOfMonth();
                $from = $month->copy();
                $to = $month->copy()->endOfMonth()->startOfDay();
                $input = ['month' => $from->format('Y-m')];
        }

        return new self($period, $from, $to, $input);
    }

    public function label(): string
    {
        return match ($this->period) {
            'month' => ucfirst($this->from->isoFormat('MMMM YYYY')),
            'quarter' => $this->input['quarter'].'° trimestre '.$this->input['year'],
            'year' => 'Anno '.$this->input['year'],
            default => 'Dal '.$this->from->format('d/m/Y').' al '.$this->to->format('d/m/Y'),
        };
    }

    /** Parametri per mantenere il periodo nei link. */
    public function query(): array
    {
        return ['period' => $this->period] + $this->input;
    }

    /** I mesi (primo giorno) toccati dal periodo: i compensi si calcolano mese per mese. */
    public function months(): array
    {
        $out = [];
        $cursor = $this->from->copy()->startOfMonth();
        while ($cursor->lte($this->to)) {
            $out[] = $cursor->copy();
            $cursor->addMonth();
        }

        return $out;
    }

    private static function intIn(mixed $value, int $min, int $max, int $default): int
    {
        $n = filter_var($value, FILTER_VALIDATE_INT);

        return $n !== false && $n >= $min && $n <= $max ? $n : $default;
    }

    private static function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        try {
            return Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function month(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            return null;
        }

        return Carbon::createFromFormat('!Y-m', $value);
    }
}
