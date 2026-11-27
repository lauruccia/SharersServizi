<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use RuntimeException;

/** Legge il "ReportDealer" (.xls, .xlsx o .csv) e restituisce le righe come array associativi (intestazione => valore). */
class ReportDealerReader
{
    public const REQUIRED = ['Id Dealer', 'Dealer', 'Id Contratto', 'Servizi Richiesti', 'Data Stipula', 'Ultimo Stato Ctr'];

    /** @return array<int, array<string, mixed>> */
    public function read(string $path): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($path)->getActiveSheet();
            $grid = $sheet->toArray(null, true, false, false);
        } catch (\Throwable $e) {
            throw new RuntimeException('Il file non è un foglio Excel/CSV leggibile ('.$e->getMessage().').');
        }

        $header = array_map(fn ($h) => trim((string) $h), array_shift($grid) ?? []);
        $missing = array_values(array_diff(self::REQUIRED, $header));
        if ($missing) {
            throw new RuntimeException('Mancano colonne obbligatorie: '.implode(', ', $missing).'.');
        }

        $rows = [];
        foreach ($grid as $line) {
            if (! array_filter($line, fn ($v) => $v !== null && $v !== '')) {
                continue;
            }
            $row = [];
            foreach ($header as $i => $name) {
                if ($name !== '') {
                    $row[$name] = $line[$i] ?? null;
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** Data come "dd/mm/yyyy [hh:mm:ss]", come data Excel o come yyyy-mm-dd. */
    public static function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            if (is_numeric($value)) {
                return Carbon::instance(Date::excelToDateTimeObject((float) $value));
            }
            $text = trim((string) $value);
            if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$#', $text, $m)) {
                return Carbon::create((int) $m[3], (int) $m[2], (int) $m[1], (int) ($m[4] ?? 0), (int) ($m[5] ?? 0), (int) ($m[6] ?? 0));
            }

            return Carbon::parse($text);
        } catch (\Throwable) {
            return null;
        }
    }
}
