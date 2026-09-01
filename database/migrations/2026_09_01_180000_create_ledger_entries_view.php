<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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
                e.vendor,
                NULL,
                e.category,
                e.description
            FROM expenses e
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ledger_entries');
    }
};
