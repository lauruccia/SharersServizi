<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('ambassadors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('dealers', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('ambassador_id')->nullable()->constrained('ambassadors')->nullOnDelete();
        });

        Schema::create('production_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('filename');
            $table->unsignedInteger('rows_read')->default(0);
            $table->unsignedInteger('rows_created')->default(0);
            $table->unsignedInteger('rows_updated')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->unsignedInteger('dealers_created')->default(0);
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->json('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // Una riga per contratto/servizio del report dealer (chiave: Id Contratto).
        Schema::create('production_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_import_id')->nullable()->constrained('production_imports')->nullOnDelete();
            $table->foreignId('dealer_id')->nullable()->constrained('dealers')->nullOnDelete();
            $table->string('contract_id', 30)->unique();
            $table->string('unique_id', 30)->nullable()->index();
            $table->string('external_dealer_id', 30)->index();
            $table->string('dealer_name')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('service_raw', 60);
            $table->string('service_type', 30)->index(); // sim | luce | gas | fibra | voip | h24 | tutela_legale | videoconsulto_medico | videoconsulto_veterinario | altro
            $table->boolean('is_consumer')->default(true);
            $table->date('signed_at')->index(); // Data Stipula: è la data che fa fede
            $table->date('signed_month')->index();
            $table->string('status')->nullable(); // Ultimo Stato Ctr
            $table->dateTime('status_at')->nullable();
            $table->string('outcome', 12)->index(); // active | pending | excluded (calcolato all'import)
            $table->boolean('force_count')->nullable(); // true = conta sempre, false = non conta mai, null = automatico
            $table->string('discard_reason')->nullable(); // Motivo Cest
            $table->string('cc_status', 30)->nullable(); // Stato CC
            $table->text('ccn_note')->nullable(); // Nota CCN
            $table->boolean('friend_code_insert')->default(false); // Codice Amico Insert_Pda
            $table->string('payment_method', 40)->nullable(); // Modalita Pagamento
            $table->string('invoice_delivery', 40)->nullable(); // Modalita Sped. Fattura
            $table->unsignedSmallInteger('sims_sold')->default(0);
            $table->unsignedSmallInteger('sims_portability')->default(0);
            $table->boolean('sims_paid')->nullable();
            $table->string('offer_code', 40)->nullable(); // Sottotipo Ctr
            $table->string('activation_type', 40)->nullable(); // solo luce/gas (e fibra)
            $table->string('utility_status', 40)->nullable(); // Stato Utenza, solo luce/gas
            $table->timestamps();
            $table->index(['dealer_id', 'signed_month']);
        });

        Schema::create('commission_plans', function (Blueprint $table) {
            $table->id();
            $table->date('valid_from')->index(); // primo giorno del primo mese di validità
            $table->string('note')->nullable();
            $table->json('params');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_plan_id')->constrained('commission_plans')->cascadeOnDelete();
            $table->string('product', 30); // sim_495 | sim_695 | fibra | energy_consumer | energy_business
            $table->unsignedSmallInteger('min_qty');
            $table->unsignedSmallInteger('max_qty')->nullable();
            $table->decimal('base_amount', 8, 2);
            $table->decimal('mnp_amount', 8, 2)->default(0);
            $table->index(['commission_plan_id', 'product']);
        });

        Schema::create('contests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('metric', 20); // mnp_all | mnp_sim | mnp_fibra
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedInteger('target')->nullable();
            $table->string('prize')->nullable();
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Piano compensi iniziale: file "compensi negozi.xlsx" (20/11/2026). Id fisso 1 (vale anche per lo SQL di phpMyAdmin).
        DB::table('commission_plans')->insert([
            'id' => 1, 'valid_from' => '2026-01-01', 'note' => 'Piano iniziale da "compensi negozi.xlsx"',
            'params' => json_encode(\App\Models\CommissionPlan::DEFAULT_PARAMS),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (\App\Models\CommissionPlan::DEFAULT_RULES as [$product, $min, $max, $base, $mnp]) {
            DB::table('commission_rules')->insert([
                'commission_plan_id' => 1, 'product' => $product, 'min_qty' => $min, 'max_qty' => $max,
                'base_amount' => $base, 'mnp_amount' => $mnp,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contests');
        Schema::dropIfExists('commission_rules');
        Schema::dropIfExists('commission_plans');
        Schema::dropIfExists('production_rows');
        Schema::dropIfExists('production_imports');
        Schema::table('dealers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ambassador_id');
            $table->dropConstrainedForeignId('area_id');
        });
        Schema::dropIfExists('ambassadors');
        Schema::dropIfExists('areas');
    }
};
