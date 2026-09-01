<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_services', function (Blueprint $table) {
            // Specs shown under the title on invoices (one line per row),
            // e.g. "Machine Type: KVM" / "Memory up to 4GB".
            $table->text('description')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('client_services', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
