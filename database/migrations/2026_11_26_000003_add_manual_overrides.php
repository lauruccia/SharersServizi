<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_rows', function (Blueprint $table) {
            $table->boolean('locked')->default(false); // corretta a mano: un nuovo import non la sovrascrive
            $table->boolean('is_manual')->default(false); // inserita a mano dall'admin
            $table->boolean('fibra_mnp_force')->nullable(); // forza "fibra con portabilità": sì / no / automatico
            $table->string('edit_note')->nullable();
        });

        // Rettifiche manuali ai compensi di un dealer in un mese (positive o negative), in centesimi.
        Schema::create('commission_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
            $table->date('month'); // primo giorno del mese
            $table->integer('amount_cents');
            $table->string('note');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['dealer_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_adjustments');
        Schema::table('production_rows', function (Blueprint $table) {
            $table->dropColumn(['locked', 'is_manual', 'fibra_mnp_force', 'edit_note']);
        });
    }
};
