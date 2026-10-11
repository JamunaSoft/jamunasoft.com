<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tlds', function (Blueprint $table) {
            $table->boolean('auto_price_sync')->default(false);
            $table->decimal('register_usd', 10, 4)->nullable();
            $table->decimal('renew_usd', 10, 4)->nullable();
            $table->decimal('transfer_usd', 10, 4)->nullable();
            $table->timestamp('prices_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tlds', function (Blueprint $table) {
            $table->dropColumn(['auto_price_sync', 'register_usd', 'renew_usd', 'transfer_usd', 'prices_synced_at']);
        });
    }
};
