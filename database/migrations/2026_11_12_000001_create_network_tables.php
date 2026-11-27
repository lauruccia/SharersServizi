<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone', 40)->nullable();
            $table->string('region', 60)->nullable();
            $table->string('zones')->nullable();
            $table->text('experience')->nullable();
            $table->timestamps();
        });

        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('business_name');
            $table->string('contact_name')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('activity_type', 80)->nullable();
            $table->string('region', 60)->nullable();
            $table->string('municipality', 120)->nullable();
            // Codice con cui il dealer compare nei report importati.
            $table->string('external_code', 60)->nullable()->unique();
            // Anagrafica fiscale, compilata dall'agente.
            $table->string('vat_number', 20)->nullable();
            $table->string('sdi_code', 10)->nullable();
            $table->string('iban', 40)->nullable();
            $table->string('pec')->nullable();
            $table->string('legal_address')->nullable();
            $table->string('operating_address')->nullable();
            $table->string('legal_rep_mobile', 40)->nullable();
            $table->string('billing_regime', 30)->nullable();
            // Finché è vuoto il dealer non può ordinare.
            $table->timestamp('registry_completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('registration_requests', function (Blueprint $table) {
            $table->id();
            $table->string('profile', 10)->index(); // dealer | agent
            $table->string('status', 20)->default('in_attesa')->index(); // in_attesa | approvata | rifiutata
            $table->string('name'); // referente (dealer) o nome e cognome (agente)
            $table->string('business_name')->nullable();
            $table->string('phone', 40);
            $table->string('email');
            $table->string('activity_type', 80)->nullable();
            $table->string('region', 60);
            $table->string('municipality', 120)->nullable();
            $table->string('zones')->nullable();
            $table->text('experience')->nullable();
            $table->dateTime('meeting_at');
            $table->foreignId('meeting_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('google_event_id')->nullable();
            $table->string('meet_url')->nullable();
            $table->timestamp('privacy_accepted_at');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['meeting_manager_id', 'meeting_at']);
        });

        Schema::create('registration_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_messages');
        Schema::dropIfExists('registration_requests');
        Schema::dropIfExists('dealers');
        Schema::dropIfExists('agents');
    }
};
