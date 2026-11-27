<?php

namespace App\Services;

use App\Models\Ambassador;
use App\Models\Area;
use App\Models\Dealer;
use App\Models\ProductionImport;
use App\Models\ProductionRow;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importa il ReportDealer: una riga per contratto (chiave "Id Contratto"). Ricaricare lo stesso file, o uno
 * più recente, aggiorna le righe esistenti senza duplicarle e conserva il flag "forza conteggio" deciso dall'admin.
 */
class ProductionImporter
{
    /** Stato che rende il contratto un'attivazione valida. */
    public const STATUS_OK = 'OK Attivazione';

    /** Stati chiusi in negativo: non si conteggiano. Gli altri stati sono "in lavorazione". */
    public const EXCLUDED_STATES = ['Contratto Cestinato', 'CheckCard Negativa'];

    /** Motivi di scarto che escludono il contratto (confronto sull'inizio, il file ha caratteri accentati rovinati). */
    public const EXCLUDED_REASONS = ['qcc ko', 'ko solvibilit'];

    public function __construct(private readonly ReportDealerReader $reader) {}

    public function import(string $path, string $filename, ?User $user = null): ProductionImport
    {
        $rows = $this->reader->read($path);

        return DB::transaction(function () use ($rows, $filename, $user) {
            $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'dealers' => 0];
            $notes = ['skipped' => [], 'linked_by_name' => [], 'new_dealers' => []];
            $min = $max = null;

            $existing = ProductionRow::query()
                ->whereIn('contract_id', collect($rows)->map(fn ($r) => $this->text($r['Id Contratto'] ?? null))->filter()->unique()->all())
                ->get()->keyBy('contract_id');
            $dealerCache = [];

            $import = ProductionImport::query()->create(['user_id' => $user?->id, 'filename' => $filename, 'rows_read' => count($rows), 'created_at' => now()]);

            foreach ($rows as $i => $r) {
                $line = $i + 2;
                $contractId = $this->text($r['Id Contratto'] ?? null);
                $externalId = $this->text($r['Id Dealer'] ?? null);
                $signed = ReportDealerReader::parseDate($r['Data Stipula'] ?? null);
                $serviceRaw = $this->text($r['Servizi Richiesti'] ?? null);

                $problem = match (true) {
                    ! $contractId => 'Id Contratto mancante',
                    ! $externalId => 'Id Dealer mancante',
                    ! $signed => 'Data Stipula mancante o non valida',
                    ! $serviceRaw => 'Servizio mancante',
                    default => null,
                };
                if ($problem) {
                    $stats['skipped']++;
                    if (count($notes['skipped']) < 50) {
                        $notes['skipped'][] = "Riga $line: $problem";
                    }

                    continue;
                }

                $dealerCache[$externalId] ??= $this->resolveDealer($r, $externalId, $stats, $notes);
                $dealer = $dealerCache[$externalId];

                [$type, $consumer] = self::classifyService($serviceRaw);
                $status = $this->text($r['Ultimo Stato Ctr'] ?? null);
                $reason = $this->text($r['Motivo Cest'] ?? null);

                $sims = $type === 'sim' ? (int) ($r['nSimVendute'] ?? 0) : 0;
                $portab = (int) ($r['nSimPortabilita'] ?? 0);
                $data = [
                    'production_import_id' => $import->id,
                    'dealer_id' => $dealer->id,
                    'unique_id' => $this->text($r['Id Unico'] ?? null),
                    'external_dealer_id' => $externalId,
                    'dealer_name' => $this->text($r['Dealer'] ?? null),
                    'customer_name' => $this->text($r['Cliente'] ?? null),
                    'service_raw' => $serviceRaw,
                    'service_type' => $type,
                    'is_consumer' => $consumer,
                    'signed_at' => $signed->toDateString(),
                    'signed_month' => $signed->copy()->startOfMonth()->toDateString(),
                    'status' => $status,
                    'status_at' => ReportDealerReader::parseDate($r['Data Ultimo Stato Ctr'] ?? null),
                    'outcome' => self::outcome($status, $reason),
                    'discard_reason' => $reason,
                    'cc_status' => $this->text($r['Stato CC'] ?? null),
                    'ccn_note' => $this->text($r['Nota CCN'] ?? null),
                    'friend_code_insert' => strcasecmp((string) ($r['Codice Amico Insert_Pda'] ?? ''), 'si') === 0,
                    'payment_method' => $this->text($r['Modalita Pagamento'] ?? null),
                    'invoice_delivery' => $this->text($r['Modalita Sped. Fattura'] ?? null),
                    'sims_sold' => $sims,
                    'sims_portability' => $type === 'sim' ? min($portab, $sims) : $portab,
                    'sims_paid' => isset($r['simPagate']) && $r['simPagate'] !== '' ? strcasecmp((string) $r['simPagate'], 'si') === 0 : null,
                    'offer_code' => $this->text($r['Sottotipo Ctr'] ?? null),
                    'activation_type' => $this->text($r['Tipo Attivazione'] ?? null),
                    'utility_status' => $this->text($r['Stato Utenza'] ?? null),
                ];

                if ($row = $existing->get($contractId)) {
                    if ($row->locked) {
                        // Corretta a mano dall'admin: l'import non la tocca.
                        $notes['protected'] = ($notes['protected'] ?? 0) + 1;
                    } else {
                        $row->update($data);
                        $stats['updated']++;
                    }
                } else {
                    $new = ProductionRow::query()->create($data + ['contract_id' => $contractId]);
                    $existing->put($contractId, $new);
                    $stats['created']++;
                }
                $min = $min ? min($min, $signed->toDateString()) : $signed->toDateString();
                $max = $max ? max($max, $signed->toDateString()) : $signed->toDateString();
            }

            $import->update([
                'rows_created' => $stats['created'], 'rows_updated' => $stats['updated'], 'rows_skipped' => $stats['skipped'],
                'dealers_created' => $stats['dealers'], 'period_from' => $min, 'period_to' => $max,
                'notes' => array_filter($notes),
            ]);

            return $import;
        });
    }

    /** @return array{0: string, 1: bool} tipo di servizio e consumer (suffisso _C) */
    public static function classifyService(string $raw): array
    {
        $key = strtoupper(trim($raw));
        $consumer = str_ends_with($key, '_C');
        $base = $consumer ? substr($key, 0, -2) : $key;

        $type = match ($base) {
            'MOBILE' => 'sim',
            'ENERGIA' => 'luce',
            'GAS' => 'gas',
            'ADSL' => 'fibra',
            'VOIP' => 'voip', // opzione dell'ADSL: non si conteggia
            'H24' => 'h24',
            'TUTELALEGALE' => 'tutela_legale',
            'VIDEOCONSULTO_MEDICO' => 'videoconsulto_medico',
            'VIDEOCONSULTO_VETERINARIO' => 'videoconsulto_veterinario',
            default => 'altro',
        };

        return [$type, $consumer];
    }

    public static function outcome(?string $status, ?string $reason): string
    {
        $reason = Str::lower(trim((string) $reason));
        foreach (self::EXCLUDED_REASONS as $prefix) {
            if ($reason !== '' && str_starts_with($reason, $prefix)) {
                return ProductionRow::OUTCOME_EXCLUDED;
            }
        }
        if (in_array($status, self::EXCLUDED_STATES, true) || $reason !== '') {
            return ProductionRow::OUTCOME_EXCLUDED;
        }

        return $status === self::STATUS_OK ? ProductionRow::OUTCOME_ACTIVE : ProductionRow::OUTCOME_PENDING;
    }

    /** Il dealer del report: per codice (Id Dealer); se non c'è si collega per nome o lo si crea. */
    private function resolveDealer(array $r, string $externalId, array &$stats, array &$notes): Dealer
    {
        $name = $this->text($r['Dealer'] ?? null) ?? ('Dealer '.$externalId);
        $area = ($n = $this->text($r['Area'] ?? null)) ? Area::query()->firstOrCreate(['name' => $n]) : null;
        $ambassador = ($n = $this->text($r['Ambassador'] ?? null))
            ? Ambassador::query()->firstOrCreate(['name' => $n], ['area_id' => $area?->id]) : null;

        $dealer = Dealer::query()->where('external_code', $externalId)->first();
        if (! $dealer) {
            $dealer = Dealer::query()->whereNull('external_code')->whereRaw('lower(business_name) = ?', [Str::lower($name)])->first();
            if ($dealer) {
                $dealer->update(['external_code' => $externalId]);
                $notes['linked_by_name'][] = $name;
            } else {
                $dealer = Dealer::query()->create(['business_name' => $name, 'external_code' => $externalId]);
                $stats['dealers']++;
                $notes['new_dealers'][] = $name;
            }
        }

        // Area e ambassador del report riempiono solo i vuoti: le scelte dell'admin non si sovrascrivono.
        $fill = array_filter([
            'area_id' => $dealer->area_id ? null : $area?->id,
            'ambassador_id' => $dealer->ambassador_id ? null : $ambassador?->id,
        ]);
        if ($fill) {
            $dealer->update($fill);
        }

        return $dealer;
    }

    private function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim(is_float($value) && floor($value) === $value ? (string) (int) $value : (string) $value);

        return $text === '' ? null : $text;
    }
}
