<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Secret token for the public invoice link (same pattern as quotations).
            $table->string('token', 64)->nullable()->unique()->after('reference');
        });

        DB::table('invoices')->whereNull('token')->orderBy('id')->each(function ($invoice) {
            DB::table('invoices')->where('id', $invoice->id)->update(['token' => Str::random(40)]);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
};
