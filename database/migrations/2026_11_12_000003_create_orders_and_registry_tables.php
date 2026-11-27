<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table) {
            // Anagrafica inviata dall'agente, in attesa della verifica del back office.
            $table->timestamp('registry_submitted_at')->nullable();
            $table->foreignId('registry_submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('expected_sim')->nullable();
            $table->unsignedInteger('expected_energy')->nullable();
            $table->unsignedInteger('expected_fiber')->nullable();
            $table->timestamp('privacy_confirmed_at')->nullable();
        });

        Schema::create('dealer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // visura | documento | foto_interna | foto_esterna
            $table->string('path');
            $table->string('original_name');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->nullable()->unique();
            $table->string('type', 20)->index(); // optima | altri_operatori | promo | generico
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // chi ha inserito l'ordine
            $table->foreignId('dealer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('business_name');
            $table->string('delivery_address');
            $table->string('customer_email');
            $table->string('agent_name')->nullable();
            $table->date('order_date');
            $table->string('goods_type')->nullable();
            // null = spese di trasporto ancora da quantificare (promo e ordine generico).
            $table->decimal('shipping_cost', 10, 2)->nullable();
            $table->decimal('total', 10, 2);
            $table->string('vat_note')->nullable();
            $table->string('payment_method', 20); // bonifico | carta
            $table->string('payment_status', 20)->default('in_attesa')->index(); // in_attesa | pagato
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stripe_session_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('amount', 10, 2);
            $table->string('note')->nullable();
            $table->foreignId('promo_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('dealer_documents');
        Schema::table('dealers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('registry_submitted_by');
            $table->dropColumn(['registry_submitted_at', 'expected_sim', 'expected_energy', 'expected_fiber', 'privacy_confirmed_at']);
        });
    }
};
