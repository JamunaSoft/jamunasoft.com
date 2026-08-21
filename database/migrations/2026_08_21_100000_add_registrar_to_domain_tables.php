<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->string('registrar')->default('spaceship')->after('name')->index();
        });

        Schema::table('domain_orders', function (Blueprint $table) {
            $table->string('registrar')->default('spaceship')->after('domain_name');
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn('registrar');
        });

        Schema::table('domain_orders', function (Blueprint $table) {
            $table->dropColumn('registrar');
        });
    }
};
