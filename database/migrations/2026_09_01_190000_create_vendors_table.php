<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('vendor')->constrained()->nullOnDelete();
        });

        // Promote existing free-text vendor names into vendor records.
        DB::table('expenses')
            ->whereNotNull('vendor')
            ->where('vendor', '!=', '')
            ->distinct()
            ->pluck('vendor')
            ->each(function (string $name) {
                $vendorId = DB::table('vendors')->insertGetId([
                    'name' => trim($name),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('expenses')->where('vendor', $name)->update(['vendor_id' => $vendorId]);
            });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('vendor');
        });

        // The ledger view must now read the vendor name via the relation.
        DB::statement('DROP VIEW IF EXISTS ledger_entries');
        DB::statement(<<<'SQL'
            CREATE VIEW ledger_entries AS
            SELECT
                CONCAT('P', p.id)   AS id,
                'in'                AS direction,
                p.paid_at           AS happened_at,
                p.amount            AS amount,
                p.amount            AS signed_amount,
                p.method            AS method,
                p.transaction_id    AS reference,
                u.name              AS counterparty,
                i.reference         AS invoice_reference,
                NULL                AS category,
                NULL                AS description
            FROM payments p
            LEFT JOIN users u ON u.id = p.user_id
            LEFT JOIN invoices i ON i.id = p.invoice_id
            UNION ALL
            SELECT
                CONCAT('E', e.id),
                'out',
                e.expensed_at,
                e.amount,
                -e.amount,
                e.method,
                NULL,
                v.name,
                NULL,
                e.category,
                e.description
            FROM expenses e
            LEFT JOIN vendors v ON v.id = e.vendor_id
        SQL);
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('vendor')->nullable()->after('description');
            $table->dropConstrainedForeignId('vendor_id');
        });

        Schema::dropIfExists('vendors');
    }
};
