<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vendor;
use App\Services\InvoicePdfRenderer;
use App\Services\StatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class StatementController extends Controller
{
    public function client(Request $request, StatementService $statements, InvoicePdfRenderer $renderer, User $user): Response
    {
        abort_unless($request->user()->roles()->exists(), 403);

        [$from, $to] = $this->period($request);

        $pdf = Pdf::loadView('pdf.statement-client', [
            'client' => $user,
            'statement' => $statements->forClient($user, $from, $to),
            'from' => $from,
            'to' => $to ?? now(),
            'logo' => $renderer->logoDataUri(),
        ])->setPaper('a4')->setOption('isFontSubsettingEnabled', true);

        return $pdf->stream('statement-'.str($user->name)->slug().'.pdf');
    }

    public function vendor(Request $request, StatementService $statements, InvoicePdfRenderer $renderer, Vendor $vendor): Response
    {
        abort_unless($request->user()->roles()->exists(), 403);

        [$from, $to] = $this->period($request);

        $pdf = Pdf::loadView('pdf.statement-vendor', [
            'vendor' => $vendor,
            'statement' => $statements->forVendor($vendor, $from, $to),
            'from' => $from,
            'to' => $to ?? now(),
            'logo' => $renderer->logoDataUri(),
        ])->setPaper('a4')->setOption('isFontSubsettingEnabled', true);

        return $pdf->stream('statement-'.str($vendor->name)->slug().'.pdf');
    }

    /** @return array{0: ?Carbon, 1: ?Carbon} */
    protected function period(Request $request): array
    {
        return [
            $request->filled('from') ? Carbon::parse($request->query('from')) : null,
            $request->filled('to') ? Carbon::parse($request->query('to')) : null,
        ];
    }
}
