<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\VeriFactuService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateInvoicesFingerprint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:update-fingerprint {--all : Update all invoices} {invoice_id? : The invoice ID to update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update digital fingerprint for invoices';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $veriFactuService = new VeriFactuService();
        
        if ($this->option('all')) {
            $this->info('Updating all invoices...');
            $invoices = Invoice::all();
        } elseif ($this->argument('invoice_id')) {
            $invoice = Invoice::find($this->argument('invoice_id'));
            if (!$invoice) {
                $this->error('Invoice not found');
                return 1;
            }
            $invoices = collect([$invoice]);
        } else {
            $this->info('Updating invoices without fingerprint...');
            $invoices = Invoice::whereNull('verifactu_hash')->get();
        }

        $bar = $this->output->createProgressBar(count($invoices));
        $bar->start();

        $updated = 0;
        $errors = 0;

        foreach ($invoices as $invoice) {
            try {
                $fingerprintData = $veriFactuService->generateDigitalFingerprint($invoice);
                
                $invoice->update([
                    'verifactu_hash' => $fingerprintData['verifactu_hash'],
                    'verifactu_signature' => $fingerprintData['verifactu_signature'],
                    'verifactu_timestamp' => $fingerprintData['verifactu_timestamp']
                ]);
                
                $updated++;
            } catch (\Exception $e) {
                Log::error("Error updating fingerprint for invoice #{$invoice->id}: " . $e->getMessage());
                $this->error("Error updating invoice #{$invoice->id}: " . $e->getMessage());
                $errors++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("Updated {$updated} invoices successfully.");
        if ($errors > 0) {
            $this->warn("Failed to update {$errors} invoices. Check the logs for details.");
        }
        
        return 0;
    }
}
