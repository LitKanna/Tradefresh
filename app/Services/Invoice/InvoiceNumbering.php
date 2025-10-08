<?php

namespace App\Services\Invoice;

use App\Models\Invoice;
use App\Models\CreditNote;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceNumbering
{
    /**
     * Generate sequential invoice number
     */
    public function generateInvoiceNumber(): string
    {
        return DB::transaction(function () {
            $prefix = 'INV';
            $year = Carbon::now()->format('Y');
            $month = Carbon::now()->format('m');
            
            // Get the last invoice number for this month
            $lastInvoice = Invoice::where('invoice_number', 'like', "{$prefix}-{$year}{$month}-%")
                ->orderBy('invoice_number', 'desc')
                ->lockForUpdate()
                ->first();
            
            if ($lastInvoice) {
                // Extract the sequence number
                $lastNumber = (int) substr($lastInvoice->invoice_number, -6);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            
            // Format: INV-YYYYMM-000001
            return sprintf("%s-%s%s-%06d", $prefix, $year, $month, $nextNumber);
        });
    }
    
    /**
     * Generate proforma invoice number
     */
    public function generateProformaNumber(): string
    {
        return DB::transaction(function () {
            $prefix = 'PRO';
            $year = Carbon::now()->format('Y');
            $month = Carbon::now()->format('m');
            
            $lastInvoice = Invoice::where('invoice_number', 'like', "{$prefix}-{$year}{$month}-%")
                ->orderBy('invoice_number', 'desc')
                ->lockForUpdate()
                ->first();
            
            if ($lastInvoice) {
                $lastNumber = (int) substr($lastInvoice->invoice_number, -6);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            
            return sprintf("%s-%s%s-%06d", $prefix, $year, $month, $nextNumber);
        });
    }
    
    /**
     * Generate credit note number
     */
    public function generateCreditNoteNumber(): string
    {
        return DB::transaction(function () {
            $prefix = 'CN';
            $year = Carbon::now()->format('Y');
            $month = Carbon::now()->format('m');
            
            $lastCreditNote = CreditNote::where('credit_note_number', 'like', "{$prefix}-{$year}{$month}-%")
                ->orderBy('credit_note_number', 'desc')
                ->lockForUpdate()
                ->first();
            
            if ($lastCreditNote) {
                $lastNumber = (int) substr($lastCreditNote->credit_note_number, -6);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            
            return sprintf("%s-%s%s-%06d", $prefix, $year, $month, $nextNumber);
        });
    }
    
    /**
     * Generate RCTI number
     */
    public function generateRCTINumber(): string
    {
        return DB::transaction(function () {
            $prefix = 'RCTI';
            $year = Carbon::now()->format('Y');
            $month = Carbon::now()->format('m');
            
            $lastInvoice = Invoice::where('invoice_number', 'like', "{$prefix}-{$year}{$month}-%")
                ->where('type', 'rcti')
                ->orderBy('invoice_number', 'desc')
                ->lockForUpdate()
                ->first();
            
            if ($lastInvoice) {
                $lastNumber = (int) substr($lastInvoice->invoice_number, -6);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            
            return sprintf("%s-%s%s-%06d", $prefix, $year, $month, $nextNumber);
        });
    }
    
    /**
     * Generate delivery docket number
     */
    public function generateDeliveryDocketNumber(): string
    {
        return DB::transaction(function () {
            $prefix = 'DD';
            $date = Carbon::now()->format('Ymd');
            
            $lastDocket = DB::table('delivery_dockets')
                ->where('docket_number', 'like', "{$prefix}-{$date}-%")
                ->orderBy('docket_number', 'desc')
                ->lockForUpdate()
                ->first();
            
            if ($lastDocket) {
                $lastNumber = (int) substr($lastDocket->docket_number, -4);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            
            return sprintf("%s-%s-%04d", $prefix, $date, $nextNumber);
        });
    }
    
    /**
     * Generate payment receipt number
     */
    public function generateReceiptNumber(): string
    {
        return DB::transaction(function () {
            $prefix = 'REC';
            $year = Carbon::now()->format('Y');
            $month = Carbon::now()->format('m');
            
            $lastReceipt = DB::table('payment_receipts')
                ->where('receipt_number', 'like', "{$prefix}-{$year}{$month}-%")
                ->orderBy('receipt_number', 'desc')
                ->lockForUpdate()
                ->first();
            
            if ($lastReceipt) {
                $lastNumber = (int) substr($lastReceipt->receipt_number, -6);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            
            return sprintf("%s-%s%s-%06d", $prefix, $year, $month, $nextNumber);
        });
    }
    
    /**
     * Validate invoice number format
     */
    public function validateInvoiceNumber(string $invoiceNumber): bool
    {
        // Pattern: PREFIX-YYYYMM-000000
        $pattern = '/^(INV|PRO|RCTI)-\d{6}-\d{6}$/';
        return preg_match($pattern, $invoiceNumber) === 1;
    }
    
    /**
     * Validate credit note number format
     */
    public function validateCreditNoteNumber(string $creditNoteNumber): bool
    {
        // Pattern: CN-YYYYMM-000000
        $pattern = '/^CN-\d{6}-\d{6}$/';
        return preg_match($pattern, $creditNoteNumber) === 1;
    }
    
    /**
     * Get next available number for a given prefix
     */
    public function getNextAvailableNumber(string $prefix): int
    {
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        
        $table = match($prefix) {
            'CN' => 'credit_notes',
            'REC' => 'payment_receipts',
            'DD' => 'delivery_dockets',
            default => 'invoices',
        };
        
        $column = match($prefix) {
            'CN' => 'credit_note_number',
            'REC' => 'receipt_number',
            'DD' => 'docket_number',
            default => 'invoice_number',
        };
        
        $lastRecord = DB::table($table)
            ->where($column, 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy($column, 'desc')
            ->first();
        
        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->$column, -6);
            return $lastNumber + 1;
        }
        
        return 1;
    }
    
    /**
     * Reserve a number to prevent duplicates
     */
    public function reserveNumber(string $type, string $number): bool
    {
        try {
            DB::table('reserved_numbers')->insert([
                'type' => $type,
                'number' => $number,
                'reserved_at' => now(),
                'expires_at' => now()->addMinutes(5), // Reservation expires in 5 minutes
            ]);
            
            return true;
        } catch (\Exception $e) {
            return false; // Number already reserved
        }
    }
    
    /**
     * Release a reserved number
     */
    public function releaseNumber(string $type, string $number): void
    {
        DB::table('reserved_numbers')
            ->where('type', $type)
            ->where('number', $number)
            ->delete();
    }
    
    /**
     * Clean up expired reservations
     */
    public function cleanupExpiredReservations(): int
    {
        return DB::table('reserved_numbers')
            ->where('expires_at', '<', now())
            ->delete();
    }
}