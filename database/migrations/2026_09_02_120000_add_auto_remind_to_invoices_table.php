<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Opt-in automatic payment/overdue reminders (off by default —
            // the admin flips it on per invoice; manual reminders always work).
            $table->boolean('auto_remind')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('auto_remind');
        });
    }
};
