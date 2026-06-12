<?php

namespace App\Console\Commands;

use App\Models\Receivable;
use App\Models\Sale;
use Illuminate\Console\Command;

class SyncSaleFromReceivable extends Command
{
    protected $signature = 'app:sync-sale-from-receivable';

    protected $description = 'Sinkronkan status dan paid_amount Sale dari data piutang yang sudah lunas';

    public function handle(): int
    {
        $this->info('Memulai sinkronisasi Sale dari Receivable...');

        Receivable::where('status', 'partial')->update(['status' => 'open']);

        $sales = Sale::whereHas('receivables')->with('receivables')->get();

        $updated = 0;

        foreach ($sales as $sale) {
            $totalPaid = $sale->receivables->sum('paid_amount');
            $newStatus = (float) $sale->total <= (float) $totalPaid ? 'paid' : 'unpaid';

            if ($sale->status !== $newStatus || (float) $sale->paid_amount !== (float) $totalPaid) {
                $sale->update([
                    'status' => $newStatus,
                    'paid_amount' => $totalPaid,
                ]);
                $updated++;
            }
        }

        $this->info("Selesai. {$updated} dari {$sales->count()} Sale berhasil disinkronkan.");

        return self::SUCCESS;
    }
}
