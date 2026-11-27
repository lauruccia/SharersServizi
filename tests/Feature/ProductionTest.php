<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AreaHead;
use App\Models\CommissionPlan;
use App\Models\Contest;
use App\Models\Dealer;
use App\Models\ProductionRow;
use App\Models\User;
use App\Services\DateRange;
use App\Services\EarningsCalculator;
use App\Services\ProductionImporter;
use App\Services\ProductionReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/** Import del ReportDealer, calcolo di conteggi e compensi, visibilità per ruolo, filtri data, elenchi e piano. */
class ProductionTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = ['Id Dealer', 'Dealer', 'Area', 'Ambassador', 'Id Contratto', 'Id Unico', 'Cliente', 'Servizi Richiesti', 'Modalita Pagamento',
        'Modalita Sped. Fattura', 'Data Stipula', 'Ultimo Stato Ctr', 'Motivo Cest', 'Stato CC', 'nSimVendute', 'nSimPortabilita', 'Sottotipo Ctr', 'Tipo Attivazione', 'simPagate'];

    private int $seq = 1000;

    /** Riga del report con valori predefiniti: SIM 4,95, attiva, 1 SIM senza portabilità. */
    private function line(array $over = []): array
    {
        $base = ['Id Dealer' => '500', 'Dealer' => 'Negozio Uno', 'Area' => 'Area Sud', 'Ambassador' => 'Amb Rossi', 'Id Contratto' => (string) ++$this->seq,
            'Id Unico' => (string) $this->seq, 'Cliente' => 'Mario Test', 'Servizi Richiesti' => 'MOBILE_C', 'Modalita Pagamento' => 'ADD. CARTA CREDITO',
            'Modalita Sped. Fattura' => null, 'Data Stipula' => '05/11/2026', 'Ultimo Stato Ctr' => 'OK Attivazione', 'Motivo Cest' => null, 'Stato CC' => 'Positivo',
            'nSimVendute' => 1, 'nSimPortabilita' => 0, 'Sottotipo Ctr' => 'S_MOB_DE_0926', 'Tipo Attivazione' => null, 'simPagate' => 'Si'];

        return array_replace($base, $over);
    }

    private function file(array $lines, string $name = 'report.xlsx'): string
    {
        $sheet = ($book = new Spreadsheet)->getActiveSheet();
        $sheet->fromArray(self::HEADER, null, 'A1');
        foreach ($lines as $i => $l) {
            $sheet->fromArray(array_map(fn ($h) => $l[$h] ?? null, self::HEADER), null, 'A'.($i + 2));
        }
        $path = sys_get_temp_dir().'/'.uniqid('rd_').'_'.$name;
        (new Xlsx($book))->save($path);

        return $path;
    }

    private function import(array $lines, ?User $by = null)
    {
        return app(ProductionImporter::class)->import($this->file($lines), 'report.xlsx', $by);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // --- Import ------------------------------------------------------------

    public function test_import_classifies_services_and_outcomes(): void
    {
        $import = $this->import([
            $this->line(),                                                                       // SIM attiva
            $this->line(['Servizi Richiesti' => 'ENERGIA_C']),                                   // luce consumer
            $this->line(['Servizi Richiesti' => 'GAS']),                                         // gas business
            $this->line(['Servizi Richiesti' => 'ADSL_C']),                                      // fibra
            $this->line(['Servizi Richiesti' => 'VOIP_C']),                                      // opzione ADSL
            $this->line(['Ultimo Stato Ctr' => 'Contratto Cestinato', 'Motivo Cest' => 'KO Solvibilit??']),
            $this->line(['Ultimo Stato Ctr' => 'Contratto Cestinato', 'Motivo Cest' => 'QCC KO']),
            $this->line(['Ultimo Stato Ctr' => 'Campagna QCC']),                                 // in lavorazione
        ]);

        $this->assertSame(8, $import->rows_created);
        $this->assertSame(1, $import->dealers_created);

        $by = fn (string $contractOffset) => ProductionRow::query()->where('contract_id', (string) (1000 + $contractOffset))->first();
        $this->assertSame('sim', $by(1)->service_type);
        $this->assertTrue($by(1)->is_consumer);
        $this->assertSame('luce', $by(2)->service_type);
        $this->assertSame('gas', $by(3)->service_type);
        $this->assertFalse($by(3)->is_consumer);
        $this->assertSame('fibra', $by(4)->service_type);
        $this->assertSame('voip', $by(5)->service_type);
        $this->assertSame('active', $by(1)->outcome);
        $this->assertSame('excluded', $by(6)->outcome);
        $this->assertSame('excluded', $by(7)->outcome);
        $this->assertSame('pending', $by(8)->outcome);
        $this->assertSame('Negozio Uno', Dealer::query()->where('external_code', '500')->first()->business_name);
        $this->assertSame('Area Sud', Dealer::query()->first()->area->name);
        $this->assertSame('Amb Rossi', Dealer::query()->first()->ambassador->name);
    }

    public function test_reimport_updates_without_duplicates_and_keeps_force_flag(): void
    {
        $lines = [$this->line(['Id Contratto' => 'A1', 'Ultimo Stato Ctr' => 'Campagna QCC'])];
        $this->import($lines);
        ProductionRow::query()->where('contract_id', 'A1')->update(['force_count' => true]);

        $second = $this->import([$this->line(['Id Contratto' => 'A1', 'Ultimo Stato Ctr' => 'OK Attivazione'])]);

        $this->assertSame(1, ProductionRow::query()->count());
        $this->assertSame(1, $second->rows_updated);
        $row = ProductionRow::query()->first();
        $this->assertSame('active', $row->outcome);
        $this->assertTrue($row->force_count);
    }

    public function test_import_skips_invalid_rows_and_rejects_missing_columns(): void
    {
        $import = $this->import([$this->line(['Data Stipula' => 'boh']), $this->line(['Id Contratto' => null]), $this->line()]);
        $this->assertSame(1, $import->rows_created);
        $this->assertSame(2, $import->rows_skipped);

        $sheet = ($book = new Spreadsheet)->getActiveSheet();
        $sheet->fromArray(['Colonna a caso'], null, 'A1');
        $path = sys_get_temp_dir().'/'.uniqid('bad_').'.xlsx';
        (new Xlsx($book))->save($path);
        $this->expectException(\RuntimeException::class);
        app(ProductionImporter::class)->import($path, 'bad.xlsx');
    }

    public function test_existing_dealer_is_linked_by_name_and_manual_area_is_kept(): void
    {
        $dealer = Dealer::query()->create(['business_name' => 'negozio uno']);
        $this->import([$this->line()]);

        $this->assertSame(1, Dealer::query()->count());
        $this->assertSame('500', $dealer->fresh()->external_code);
    }

    // --- Calcolo -----------------------------------------------------------

    private function rowFor(array $attrs): ProductionRow
    {
        return new ProductionRow($attrs + ['outcome' => 'active', 'is_consumer' => true, 'sims_sold' => 0, 'sims_portability' => 0]);
    }

    public function test_earnings_follow_the_plan_with_retroactive_tiers(): void
    {
        $plan = CommissionPlan::forMonth(now()->setDate(2026, 11, 1));
        $rows = collect();
        // 10 SIM: 6 a 4,95 (2 MNP) e 4 a 6,95 (1 MNP): scaglione 10–24 sul totale.
        $rows->push($this->rowFor(['service_type' => 'sim', 'sims_sold' => 6, 'sims_portability' => 2, 'offer_code' => 'S_MOB_DE_0926']));
        $rows->push($this->rowFor(['service_type' => 'sim', 'sims_sold' => 4, 'sims_portability' => 1, 'offer_code' => 'SMOB_5G_DE_0926']));
        // 5 fibre (2 MNP): scaglione 5+.
        foreach (['MIGRASOLODATI', 'MIGRASOLODATI', 'NUOVASUSOLODATI', 'NUOVASUSOLODATI', 'NUOVASUSOLODATI'] as $t) {
            $rows->push($this->rowFor(['service_type' => 'fibra', 'activation_type' => $t]));
        }
        // 5 energy: scaglione 5–9.
        $rows->push($this->rowFor(['service_type' => 'luce', 'payment_method' => 'SDD', 'invoice_delivery' => 'Email']));              // 65+15+5
        $rows->push($this->rowFor(['service_type' => 'gas', 'payment_method' => 'Bollettino Postale', 'invoice_delivery' => 'Posta Ordinaria'])); // 65
        $rows->push($this->rowFor(['service_type' => 'luce', 'payment_method' => 'SDD', 'invoice_delivery' => 'Posta Ordinaria']));     // 65+15
        $rows->push($this->rowFor(['service_type' => 'luce', 'is_consumer' => false, 'payment_method' => 'Bollettino Postale', 'invoice_delivery' => 'Email'])); // 80+5
        $rows->push($this->rowFor(['service_type' => 'gas', 'is_consumer' => false, 'payment_method' => 'SDD', 'invoice_delivery' => 'Email']));  // 80+20

        $r = (new EarningsCalculator)->month($rows, $plan);

        $this->assertSame(13200, $r['earnings']['sim']);   // 6*10+2*8 + 4*11+1*12
        $this->assertSame(95000, $r['earnings']['fibra']); // 5*130 + 2*150
        $this->assertSame(41500, $r['earnings']['energy']);
        $this->assertSame(149700, $r['earnings']['total']);
        $this->assertSame(10, $r['metrics']['sim']);
        $this->assertSame(3, $r['metrics']['sim_mnp']);
        $this->assertSame(2, $r['metrics']['fibra_mnp']);
    }

    public function test_tier_switches_retroactively_for_all_the_month(): void
    {
        $plan = CommissionPlan::forMonth(now()->setDate(2026, 11, 1));
        $four = collect(range(1, 4))->map(fn () => $this->rowFor(['service_type' => 'fibra']));
        $five = $four->push($this->rowFor(['service_type' => 'fibra']));
        $calc = new EarningsCalculator;

        $this->assertSame(4 * 5000, $calc->month($four->take(4), $plan)['earnings']['fibra']);
        $this->assertSame(5 * 13000, $calc->month($five, $plan)['earnings']['fibra']); // tutte e 5 a 130
    }

    public function test_pending_excluded_and_forced_rows(): void
    {
        $plan = CommissionPlan::forMonth(now()->setDate(2026, 11, 1));
        $rows = collect([
            $this->rowFor(['service_type' => 'sim', 'sims_sold' => 1, 'offer_code' => 'X']),
            $this->rowFor(['service_type' => 'sim', 'sims_sold' => 1, 'offer_code' => 'X', 'outcome' => 'pending']),
            $this->rowFor(['service_type' => 'sim', 'sims_sold' => 1, 'offer_code' => 'X', 'outcome' => 'excluded']),
            $this->rowFor(['service_type' => 'sim', 'sims_sold' => 1, 'offer_code' => 'X', 'outcome' => 'excluded', 'force_count' => true]),
            $this->rowFor(['service_type' => 'sim', 'sims_sold' => 1, 'offer_code' => 'X', 'force_count' => false]),
            $this->rowFor(['service_type' => 'voip']),
        ]);

        $r = (new EarningsCalculator)->month($rows, $plan);

        $this->assertSame(2, $r['metrics']['sim']);
        $this->assertSame(1, $r['metrics']['pending_sim']);
        $this->assertSame(2 * 200, $r['earnings']['sim']); // 2 SIM a 4,95 nello scaglione 1–4: 2 € ciascuna
    }

    public function test_pending_can_be_paid_when_the_plan_says_so(): void
    {
        $plan = CommissionPlan::forMonth(now()->setDate(2026, 11, 1));
        $plan->params = ['count_pending' => true] + $plan->params;
        $rows = collect([$this->rowFor(['service_type' => 'sim', 'sims_sold' => 1, 'offer_code' => 'X', 'outcome' => 'pending'])]);

        $r = (new EarningsCalculator)->month($rows, $plan);

        $this->assertSame(0, $r['metrics']['sim']);
        $this->assertSame(200, $r['earnings']['sim']);
    }

    public function test_sim_tier_can_be_counted_per_offer(): void
    {
        $plan = CommissionPlan::forMonth(now()->setDate(2026, 11, 1));
        $plan->params = ['sim_scaglione' => 'per_offerta'] + $plan->params;
        $rows = collect([
            $this->rowFor(['service_type' => 'sim', 'sims_sold' => 5, 'offer_code' => 'S_MOB_DE_0926']),
            $this->rowFor(['service_type' => 'sim', 'sims_sold' => 5, 'offer_code' => 'SMOB_5G_DE_0926']),
        ]);

        // Per offerta: 5 SIM per tariffa → scaglione 5–9 di ciascuna (7 e 9 €). Sul totale sarebbe 10–24 (10 e 11 €).
        $this->assertSame(5 * 700 + 5 * 900, (new EarningsCalculator)->month($rows, $plan)['earnings']['sim']);
    }

    // --- Pagine, visibilità, filtri ---------------------------------------------

    /** @return array{admin: User, head: User, agent: User, dealerUser: User, otherAgent: User} */
    private function network(): array
    {
        $admin = $this->admin();
        $headUser = User::factory()->create(['role' => 'capoarea']);
        $head = AreaHead::query()->create(['user_id' => $headUser->id]);
        $agentUser = User::factory()->create(['role' => 'agent']);
        $agent = Agent::query()->create(['user_id' => $agentUser->id, 'area_head_id' => $head->id]);
        $otherUser = User::factory()->create(['role' => 'agent']);
        $other = Agent::query()->create(['user_id' => $otherUser->id]);
        $dealerUser = User::factory()->create(['role' => 'dealer']);
        $mine = Dealer::query()->create(['user_id' => $dealerUser->id, 'agent_id' => $agent->id, 'business_name' => 'Mio Negozio', 'external_code' => '1']);
        $theirs = Dealer::query()->create(['agent_id' => $other->id, 'business_name' => 'Negozio Altrui', 'external_code' => '2']);

        $this->import([
            $this->line(['Id Dealer' => '1', 'Dealer' => 'Mio Negozio', 'nSimVendute' => 3, 'nSimPortabilita' => 2]),
            $this->line(['Id Dealer' => '2', 'Dealer' => 'Negozio Altrui', 'nSimVendute' => 5, 'nSimPortabilita' => 1]),
        ]);

        return ['admin' => $admin, 'head' => $headUser, 'agent' => $agentUser, 'dealerUser' => $dealerUser, 'otherAgent' => $otherUser];
    }

    public function test_each_role_sees_only_the_dealers_below_them(): void
    {
        $n = $this->network();
        $month = ['period' => 'month', 'month' => '2026-11'];

        $this->actingAs($n['admin'])->get(route('production.index', $month))->assertOk()->assertSee('Mio Negozio')->assertSee('Negozio Altrui');
        $this->actingAs($n['agent'])->get(route('production.index', $month))->assertOk()->assertSee('Mio Negozio')->assertDontSee('Negozio Altrui');
        $this->actingAs($n['head'])->get(route('production.index', $month))->assertOk()->assertSee('Mio Negozio')->assertDontSee('Negozio Altrui');
        $this->actingAs($n['dealerUser'])->get(route('production.index', $month))->assertOk()->assertSee('Mio Negozio')->assertDontSee('Negozio Altrui');

        $theirs = Dealer::query()->where('external_code', '2')->first();
        $this->actingAs($n['agent'])->get(route('production.dealer', ['dealer' => $theirs] + $month))->assertNotFound();
        $this->actingAs($n['admin'])->get(route('production.dealer', ['dealer' => $theirs] + $month))->assertOk();
    }

    public function test_totals_show_activations_and_mnp(): void
    {
        $n = $this->network();
        $user = $n['admin'];
        $range = DateRange::fromRequest(Request::create('/?period=month&month=2026-11'));
        $report = app(ProductionReport::class);
        $totals = $report->totals($report->build($user, $range));

        $this->assertSame(8, $totals['derived']['activations']);
        $this->assertSame(3, $totals['derived']['mnp']);
    }

    public function test_date_filters_month_quarter_year_custom(): void
    {
        $this->import([
            $this->line(['Data Stipula' => '05/10/2026']),
            $this->line(['Data Stipula' => '05/11/2026']),
            $this->line(['Data Stipula' => '20/12/2026']),
            $this->line(['Data Stipula' => '10/02/2027']),
        ]);
        $admin = $this->admin();
        $report = app(ProductionReport::class);
        $sims = function (string $query) use ($admin, $report) {
            $range = DateRange::fromRequest(Request::create('/?'.$query));

            return $report->totals($report->build($admin, $range))['metrics']['sim'];
        };

        $this->assertSame(1, $sims('period=month&month=2026-11'));
        $this->assertSame(3, $sims('period=quarter&year=2026&quarter=4'));
        $this->assertSame(3, $sims('period=year&year=2026'));
        $this->assertSame(1, $sims('period=year&year=2027'));
        $this->assertSame(2, $sims('period=custom&from=2026-11-01&to=2026-12-31'));
        $this->assertSame(2, $sims('period=custom&from=2026-12-31&to=2026-11-01')); // date invertite
    }

    public function test_invalid_period_parameters_fall_back_to_the_current_month(): void
    {
        $r = DateRange::fromRequest(Request::create('/?period=boh&month=xx'));
        $this->assertSame('month', $r->period);
        $this->assertSame(now()->format('Y-m'), $r->from->format('Y-m'));
        $r2 = DateRange::fromRequest(Request::create('/?period=quarter&year=abc&quarter=9'));
        $this->assertSame(now()->year, $r2->from->year);
    }

    public function test_quarterly_earnings_are_summed_month_by_month(): void
    {
        // 4 SIM in ottobre e 4 in novembre: ogni mese resta nello scaglione 1–4 (2 € a SIM), non 8 SIM in 5–9.
        $lines = [];
        foreach (['05/10/2026', '06/10/2026', '07/10/2026', '08/10/2026', '05/11/2026', '06/11/2026', '07/11/2026', '08/11/2026'] as $d) {
            $lines[] = $this->line(['Data Stipula' => $d]);
        }
        $this->import($lines);
        $admin = $this->admin();
        $report = app(ProductionReport::class);
        $range = DateRange::fromRequest(Request::create('/?period=quarter&year=2026&quarter=4'));

        $this->assertSame(8 * 200, $report->totals($report->build($admin, $range))['earnings']['total']);
    }

    public function test_grouping_by_agent_area_and_ambassador(): void
    {
        $n = $this->network();
        $report = app(ProductionReport::class);
        $lines = $report->build($n['admin'], DateRange::fromRequest(Request::create('/?period=month&month=2026-11')));

        $this->assertCount(2, $report->groupBy($lines, 'dealer'));
        $this->assertCount(2, $report->groupBy($lines, 'agent'));
        $byAmb = $report->groupBy($lines, 'ambassador');
        $this->assertCount(1, $byAmb);
        $this->assertSame('Amb Rossi', $byAmb->first()['label']);
        $this->assertSame(2, $byAmb->first()['dealers']);
        $this->assertSame(8, $byAmb->first()['derived']['activations']);
    }

    // --- Area admin --------------------------------------------------------

    public function test_admin_imports_a_file_and_non_admin_cannot(): void
    {
        $admin = $this->admin();
        $path = $this->file([$this->line(), $this->line(['Servizi Richiesti' => 'ENERGIA_C'])]);
        $upload = fn () => new UploadedFile($path, 'ReportDealer.xlsx', null, null, true);

        $this->actingAs(User::factory()->create(['role' => 'agent']))
            ->post(route('manager.production.import.store'), ['file' => $upload()])->assertForbidden();

        $this->actingAs($admin)->post(route('manager.production.import.store'), ['file' => $upload()])
            ->assertRedirect(route('manager.production.import'));
        $this->assertSame(2, ProductionRow::query()->count());
        $this->actingAs($admin)->get(route('manager.production.import'))->assertOk()->assertSee('ReportDealer.xlsx');
    }

    public function test_import_rejects_wrong_file_type(): void
    {
        $this->actingAs($this->admin())
            ->post(route('manager.production.import.store'), ['file' => UploadedFile::fake()->create('x.pdf', 10)])
            ->assertSessionHasErrors('file');
    }

    public function test_admin_can_force_a_contract_count(): void
    {
        $this->import([$this->line(['Id Contratto' => 'K1', 'Ultimo Stato Ctr' => 'Contratto Cestinato', 'Motivo Cest' => 'QCC KO'])]);
        $row = ProductionRow::query()->first();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('manager.production.force', $row), ['mode' => 'count'])->assertRedirect();
        $this->assertSame('active', $row->fresh()->effectiveOutcome());
        $this->actingAs($admin)->post(route('manager.production.force', $row), ['mode' => 'auto'])->assertRedirect();
        $this->assertSame('excluded', $row->fresh()->effectiveOutcome());

        $this->actingAs(User::factory()->create(['role' => 'dealer']))->post(route('manager.production.force', $row), ['mode' => 'count'])->assertForbidden();
    }

    public function test_plan_edit_creates_a_new_plan_and_keeps_history(): void
    {
        $this->import([$this->line(['Data Stipula' => '05/11/2026']), $this->line(['Data Stipula' => '05/01/2027'])]);
        $admin = $this->admin();
        $rules = CommissionPlan::query()->first()->rules->map(fn ($r) => [
            'product' => $r->product, 'min_qty' => $r->min_qty, 'max_qty' => $r->max_qty, 'base_amount' => $r->base_amount, 'mnp_amount' => $r->mnp_amount,
        ])->all();
        // SIM 4,95 scaglione 1–4: da 2 a 3 €.
        $i = collect($rules)->search(fn ($r) => $r['product'] === 'sim_495' && $r['min_qty'] === 1);
        $rules[$i]['base_amount'] = '3.00';

        $this->actingAs($admin)->post(route('manager.plan.update'), [
            'valid_from' => '2027-01', 'note' => 'Nuovo piano', 'sdd_bonus' => 15, 'email_bonus' => 5, 'sim_scaglione' => 'totale',
            'fibra_mnp' => 'tipo_attivazione', 'sim_695_codes' => 'SMOB_5G_DE_0926', 'rules' => $rules,
        ])->assertRedirect();

        $this->assertSame(2, CommissionPlan::query()->count());
        $report = app(ProductionReport::class);
        $earn = fn (string $q) => $report->totals($report->build($admin, DateRange::fromRequest(Request::create('/?'.$q))))['earnings']['total'];
        $this->assertSame(200, $earn('period=month&month=2026-11')); // piano storico
        $this->assertSame(300, $earn('period=month&month=2027-01')); // piano nuovo
    }

    public function test_plan_page_shows_current_rules(): void
    {
        $this->actingAs($this->admin())->get(route('manager.plan', ['month' => '2026-11']))->assertOk()
            ->assertSee('SIM 4,95')->assertSee('Energy business')->assertSee('SMOB_5G_DE_0926');
        $this->actingAs(User::factory()->create(['role' => 'agent']))->get(route('manager.plan'))->assertForbidden();
    }

    public function test_plan_rejects_overlapping_tiers(): void
    {
        $admin = $this->admin();
        $rules = CommissionPlan::query()->first()->rules->map(fn ($r) => [
            'product' => $r->product, 'min_qty' => $r->min_qty, 'max_qty' => $r->max_qty, 'base_amount' => $r->base_amount, 'mnp_amount' => $r->mnp_amount,
        ])->all();
        $i = collect($rules)->search(fn ($r) => $r['product'] === 'sim_495' && $r['min_qty'] === 1);
        $rules[$i]['max_qty'] = 6; // 1–6 si sovrappone a 5–9

        $this->actingAs($admin)->post(route('manager.plan.update'), [
            'valid_from' => '2027-01', 'sdd_bonus' => 15, 'email_bonus' => 5, 'sim_scaglione' => 'totale', 'fibra_mnp' => 'tipo_attivazione', 'rules' => $rules,
        ])->assertSessionHasErrors('rules');
        $this->assertSame(1, CommissionPlan::query()->count());
    }

    public function test_contest_ranking_counts_mnp_in_the_period(): void
    {
        $n = $this->network();
        $this->actingAs($n['admin'])->post(route('manager.contests.store'), [
            'name' => 'Extra MNP novembre', 'metric' => 'mnp_all', 'starts_on' => '2026-11-01', 'ends_on' => '2026-11-30', 'prize' => 'Buono', 'target' => 2,
        ])->assertRedirect();
        $this->assertSame(1, Contest::query()->count());

        $this->actingAs($n['admin'])->get(route('contests.index'))->assertOk()->assertSee('Extra MNP novembre')->assertSee('Mio Negozio')->assertSee('Negozio Altrui');
        $this->actingAs($n['agent'])->get(route('contests.index'))->assertOk()->assertSee('Mio Negozio')->assertDontSee('Negozio Altrui');
        $this->actingAs($n['agent'])->post(route('manager.contests.store'), ['name' => 'x'])->assertForbidden();
    }

    public function test_admin_manages_directories(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('manager.directory.area.store'), ['name' => 'Area Nord'])->assertRedirect();
        $this->actingAs($admin)->post(route('manager.directory.area.store'), ['name' => 'Area Nord'])->assertSessionHasErrors('name');
        $area = \App\Models\Area::query()->first();
        $this->actingAs($admin)->post(route('manager.directory.ambassador.store'), ['name' => 'Bianchi', 'area_id' => $area->id])->assertRedirect();
        $amb = \App\Models\Ambassador::query()->first();
        $this->actingAs($admin)->post(route('manager.directory.dealer.store'), [
            'business_name' => 'Nuovo Dealer', 'external_code' => '777', 'area_id' => $area->id, 'ambassador_id' => $amb->id,
        ])->assertRedirect();
        $dealer = Dealer::query()->where('external_code', '777')->first();
        $this->assertSame($area->id, $dealer->area_id);

        $this->actingAs($admin)->post(route('manager.directory.dealer.update', $dealer), ['business_name' => 'Dealer Rinominato', 'external_code' => '777'])->assertRedirect();
        $this->assertSame('Dealer Rinominato', $dealer->fresh()->business_name);
        $this->actingAs($admin)->post(route('manager.directory.dealer.destroy', $dealer))->assertRedirect();
        $this->assertNull(Dealer::query()->find($dealer->id));

        foreach (['dealer', 'aree', 'ambassador', 'agenti', 'capoarea'] as $tab) {
            $this->actingAs($admin)->get(route('manager.directory', $tab))->assertOk();
        }
        $this->actingAs($admin)->post(route('manager.directory.area.destroy', $area))->assertRedirect();
        $this->assertNull($amb->fresh()->area_id);
    }

    public function test_admin_creates_agent_with_credentials_and_non_admin_is_blocked(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('manager.directory.agent.store'), ['name' => 'Nuovo Agente', 'email' => 'nuovo@example.com', 'region' => 'Calabria'])->assertRedirect();

        $user = User::query()->where('email', 'nuovo@example.com')->first();
        $this->assertSame('agent', $user->role);
        $this->assertTrue($user->must_change_password);
        $this->assertNotNull($user->agent);
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\TemporaryPasswordMail::class);

        $this->actingAs(User::factory()->create(['role' => 'capoarea']))->get(route('manager.directory'))->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('production.index'))->assertRedirect(route('login'));
    }

    // --- Correzioni manuali dell'admin -----------------------------------------------

    private function rowPayload(ProductionRow $row, array $over = []): array
    {
        return $over + [
            'dealer_id' => $row->dealer_id, 'service_raw' => $row->service_raw, 'signed_at' => $row->signed_at->toDateString(), 'status' => $row->status,
            'sims_sold' => $row->sims_sold, 'sims_portability' => $row->sims_portability, 'offer_code' => $row->offer_code,
            'fibra_mnp' => 'auto', 'force' => 'auto',
        ];
    }

    public function test_admin_can_correct_any_field_and_the_row_survives_reimport(): void
    {
        $this->import([$this->line(['Id Contratto' => 'E1', 'nSimVendute' => 1, 'nSimPortabilita' => 0])]);
        $row = ProductionRow::query()->first();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('manager.production.row.update', $row), $this->rowPayload($row, [
            'sims_sold' => 4, 'sims_portability' => 3, 'offer_code' => 'SMOB_5G_DE_0926', 'signed_at' => '2026-12-02', 'edit_note' => 'Errore Optima',
        ]))->assertRedirect();

        $row->refresh();
        $this->assertSame(4, $row->sims_sold);
        $this->assertSame('2026-12-01', $row->signed_month->toDateString());
        $this->assertTrue($row->locked);

        $second = $this->import([$this->line(['Id Contratto' => 'E1', 'nSimVendute' => 1, 'nSimPortabilita' => 0])]);
        $this->assertSame(0, $second->rows_updated);
        $this->assertSame(1, $second->notes['protected']);
        $this->assertSame(4, $row->fresh()->sims_sold);
    }

    public function test_admin_can_change_the_dealer_of_a_row(): void
    {
        $this->import([$this->line(['Id Contratto' => 'D1'])]);
        $other = Dealer::query()->create(['business_name' => 'Altro Negozio']);
        $row = ProductionRow::query()->first();

        $this->actingAs($this->admin())->post(route('manager.production.row.update', $row), $this->rowPayload($row, ['dealer_id' => $other->id]))->assertRedirect();

        $this->assertSame($other->id, $row->fresh()->dealer_id);
    }

    public function test_admin_can_force_fibra_mnp_and_status_outcome(): void
    {
        $this->import([$this->line(['Id Contratto' => 'F1', 'Servizi Richiesti' => 'ADSL_C', 'Tipo Attivazione' => 'NUOVASUSOLODATI', 'nSimVendute' => 0])]);
        $row = ProductionRow::query()->first();
        $admin = $this->admin();
        $report = app(ProductionReport::class);
        $range = DateRange::fromRequest(Request::create('/?period=month&month=2026-11'));
        $mnp = fn () => $report->totals($report->build($admin, $range))['metrics']['fibra_mnp'];

        $this->assertSame(0, $mnp());
        $this->actingAs($admin)->post(route('manager.production.row.update', $row), $this->rowPayload($row, ['fibra_mnp' => 'si']));
        $this->assertSame(1, $mnp());
        $this->actingAs($admin)->post(route('manager.production.row.update', $row), $this->rowPayload($row, ['fibra_mnp' => 'si', 'status' => 'Contratto Cestinato']));
        $this->assertSame(0, $mnp()); // stato cambiato a mano: non più attivo
    }

    public function test_admin_can_add_a_manual_contract_and_delete_it(): void
    {
        $dealer = Dealer::query()->create(['business_name' => 'Manuale Srl', 'external_code' => '9']);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('manager.production.row.store'), [
            'dealer_id' => $dealer->id, 'service_raw' => 'MOBILE_C', 'signed_at' => '2026-11-10', 'status' => 'OK Attivazione',
            'sims_sold' => 2, 'sims_portability' => 1, 'offer_code' => 'X', 'fibra_mnp' => 'auto', 'force' => 'auto',
        ])->assertRedirect();

        $row = ProductionRow::query()->first();
        $this->assertTrue($row->is_manual);
        $this->assertStringStartsWith('MAN-', $row->contract_id);
        $report = app(ProductionReport::class);
        $range = DateRange::fromRequest(Request::create('/?period=month&month=2026-11'));
        $this->assertSame(2, $report->totals($report->build($admin, $range))['metrics']['sim']);

        $this->actingAs($admin)->post(route('manager.production.row.destroy', $row))->assertRedirect();
        $this->assertSame(0, ProductionRow::query()->count());
    }

    public function test_deleting_an_imported_row_excludes_it_and_reimport_does_not_bring_it_back(): void
    {
        $this->import([$this->line(['Id Contratto' => 'X9'])]);
        $row = ProductionRow::query()->first();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('manager.production.row.destroy', $row))->assertRedirect();
        $this->import([$this->line(['Id Contratto' => 'X9'])]);

        $report = app(ProductionReport::class);
        $range = DateRange::fromRequest(Request::create('/?period=month&month=2026-11'));
        $this->assertSame(0, $report->totals($report->build($admin, $range))['metrics']['sim']);
        $this->assertSame(1, ProductionRow::query()->count());
    }

    public function test_admin_adjustments_change_earnings_and_can_be_removed(): void
    {
        $this->import([$this->line()]);
        $dealer = Dealer::query()->first();
        $admin = $this->admin();
        $report = app(ProductionReport::class);
        $range = DateRange::fromRequest(Request::create('/?period=month&month=2026-11'));
        $earn = fn () => $report->totals($report->build($admin, $range))['earnings'];

        $this->actingAs($admin)->post(route('manager.production.adjust.store'), ['dealer_id' => $dealer->id, 'month' => '2026-11', 'amount' => '-1.50', 'note' => 'Storno'])->assertRedirect();
        $this->assertSame(200, $earn()['sim']);
        $this->assertSame(-150, $earn()['adjust']);
        $this->assertSame(50, $earn()['total']);

        $this->actingAs($admin)->get(route('production.dealer', ['dealer' => $dealer, 'period' => 'month', 'month' => '2026-11']))->assertOk()->assertSee('Storno');
        $this->actingAs($admin)->post(route('manager.production.adjust.destroy', \App\Models\CommissionAdjustment::query()->first()))->assertRedirect();
        $this->assertSame(200, $earn()['total']);
    }

    public function test_adjustment_only_month_still_shows_the_dealer_and_other_months_are_unaffected(): void
    {
        $dealer = Dealer::query()->create(['business_name' => 'Solo Rettifica']);
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('manager.production.adjust.store'), ['dealer_id' => $dealer->id, 'month' => '2026-10', 'amount' => 25, 'note' => 'Bonus'])->assertRedirect();
        $report = app(ProductionReport::class);
        $total = fn (string $q) => $report->totals($report->build($admin, DateRange::fromRequest(Request::create('/?'.$q))))['earnings']['total'];

        $this->assertSame(2500, $total('period=month&month=2026-10'));
        $this->assertSame(0, $total('period=month&month=2026-11'));
        $this->assertSame(2500, $total('period=quarter&year=2026&quarter=4'));
    }

    public function test_only_admin_can_edit_rows_and_adjustments(): void
    {
        $n = $this->network();
        $row = ProductionRow::query()->first();
        $dealer = Dealer::query()->first();

        foreach (['agent', 'head', 'dealerUser'] as $who) {
            $this->actingAs($n[$who])->get(route('manager.production.row.edit', $row))->assertForbidden();
            $this->actingAs($n[$who])->post(route('manager.production.row.update', $row), $this->rowPayload($row))->assertForbidden();
            $this->actingAs($n[$who])->post(route('manager.production.row.destroy', $row))->assertForbidden();
            $this->actingAs($n[$who])->post(route('manager.production.adjust.store'), ['dealer_id' => $dealer->id, 'month' => '2026-11', 'amount' => 5, 'note' => 'x'])->assertForbidden();
        }
        $this->actingAs($n['admin'])->get(route('manager.production.row.edit', $row))->assertOk();
        $this->actingAs($n['admin'])->get(route('manager.production.row.create'))->assertOk();
    }

    public function test_row_validation_rejects_bad_input(): void
    {
        $this->import([$this->line()]);
        $row = ProductionRow::query()->first();
        $this->actingAs($this->admin())->post(route('manager.production.row.update', $row), $this->rowPayload($row, ['service_raw' => 'BOH', 'signed_at' => 'xx']))
            ->assertSessionHasErrors(['service_raw', 'signed_at']);
    }
}
