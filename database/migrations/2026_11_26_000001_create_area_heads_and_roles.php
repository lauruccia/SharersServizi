<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_heads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone', 40)->nullable();
            $table->string('region', 60)->nullable();
            $table->timestamps();
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->foreignId('area_head_id')->nullable()->after('user_id')->constrained('area_heads')->nullOnDelete();
        });

        // I vecchi "manager" (Roberto e Stefania) diventano admin.
        DB::table('users')->where('role', 'manager')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'admin')->update(['role' => 'manager']);
        Schema::table('agents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_head_id');
        });
        Schema::dropIfExists('area_heads');
    }
};
