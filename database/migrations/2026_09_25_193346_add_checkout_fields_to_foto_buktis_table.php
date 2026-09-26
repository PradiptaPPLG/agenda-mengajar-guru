<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('foto_buktis', function (Blueprint $table) {
            $table->string('foto_checkout_path')->nullable()->after('foto_path');
            $table->timestamp('checkout_at')->nullable()->after('foto_checkout_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('foto_buktis', function (Blueprint $table) {
            $table->dropColumn(['foto_checkout_path', 'checkout_at']);
        });
    }
};
