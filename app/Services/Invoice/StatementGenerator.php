<?php

namespace App\Services\Invoice;

use App\Models\Buyer;
use App\Models\Vendor;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class StatementGenerator
{
    /**
     * Generate monthly statement for a buyer
     */
    public function generateBuyerStatement(Buyer $buyer, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();
        
        // Get all invoices for the period
        $invoices = Invoice::where('buyer_id', $buyer->id)
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->with(['order', 'vendor'])
            ->orderBy('issue_date', 'asc')
            ->get();
        
        // Get all payments for the period
        $payments = Payment::where('buyer_id', $buyer->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('order')
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Calculate opening balance
        $openingBalance = $this->calculateOpeningBalance($buyer, $startDate);
        
        // Build transaction list
        $transactions = collect();
        
        // Add invoices
        foreach ($invoices as $invoice) {
            $transactions->push([
                'date' => $invoice->issue_date,
                'type' => 'invoice',
                'reference' => $invoice->invoice_number,
                'description' => "Invoice - Order #{$invoice->order->order_number}",
                'debit' => $invoice->total_amount,
                'credit' => 0,
                'vendor' => $invoice->vendor->business_name,
            ]);
        }
        
        // Add payments
        foreach ($payments as $payment) {
            $transactions->push([
                'date' => $payment->paid_at ?? $payment->created_at,
                'type' => 'payment',
                'reference' => $payment->reference_number ?? 'PAY-' . $payment->id,
                'description' => "Payment - {$payment->payment_method}",
                'debit' => 0,
                'credit' => $payment->amount,
                'order_number' => $payment->order?->order_number,
            ]);
        }
        
        // Sort by date
        $transactions = $transactions->sortBy('date');
        
        // Calculate running balance
        $runningBalance = $openingBalance;
        $transactions = $transactions->map(function ($transaction) use (&$runningBalance) {
            $runningBalance += $transaction['debit'] - $transaction['credit'];
            $transaction['balance'] = $runningBalance;
            return $transaction;
        });
        
        // Summary
        $totalDebits = $transactions->sum('debit');
        $totalCredits = $transactions->sum('credit');
        $closingBalance = $runningBalance;
        
        // Outstanding invoices
        $outstandingInvoices = Invoice::where('buyer_id', $buyer->id)
            ->where('status', '!=', 'paid')
            ->where('status', '!=', 'void')
            ->get();
        
        // Overdue amount
        $overdueAmount = $outstandingInvoices
            ->filter(fn($inv) => $inv->due_date < now())
            ->sum('total_amount');
        
        $statementData = [
            'buyer' => $buyer,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'transactions' => $transactions,
            'outstanding_invoices' => $outstandingInvoices,
            'overdue_amount' => $overdueAmount,
            'statement_date' => now(),
            'statement_number' => $this->generateStatementNumber($buyer),
        ];
        
        // Generate PDF
        $this->generateStatementPDF($statementData, 'buyer');
        
        return $statementData;
    }
    
    /**
     * Generate monthly statement for a vendor
     */
    public function generateVendorStatement(Vendor $vendor, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();
        
        // Get all orders for the period
        $orders = Order::where('vendor_id', $vendor->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['buyer', 'payment'])
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Get all payouts/transactions
        $transactions = \App\Models\Transaction::where('vendor_id', $vendor->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Build transaction list
        $transactionList = collect();
        
        // Add orders
        foreach ($orders as $order) {
            $commission = $order->total_amount * (config('stripe.platform.commission_percentage') / 100);
            $netAmount = $order->total_amount - $commission;
            
            $transactionList->push([
                'date' => $order->created_at,
                'type' => 'order',
                'reference' => $order->order_number,
                'description' => "Order from {$order->buyer->business_name}",
                'gross_amount' => $order->total_amount,
                'commission' => $commission,
                'net_amount' => $netAmount,
                'status' => $order->payment_status,
                'buyer' => $order->buyer->business_name,
            ]);
        }
        
        // Add payouts
        foreach ($transactions->where('type', 'payout') as $payout) {
            $transactionList->push([
                'date' => $payout->created_at,
                'type' => 'payout',
                'reference' => $payout->stripe_payout_id ?? 'PAYOUT-' . $payout->id,
                'description' => 'Payout to bank account',
                'gross_amount' => 0,
                'commission' => 0,
                'net_amount' => -$payout->amount, // Negative as it's money out
                'status' => $payout->status,
            ]);
        }
        
        // Sort by date
        $transactionList = $transactionList->sortBy('date');
        
        // Summary
        $totalSales = $orders->sum('total_amount');
        $totalCommission = $totalSales * (config('stripe.platform.commission_percentage') / 100);
        $netEarnings = $totalSales - $totalCommission;
        $totalPayouts = $transactions->where('type', 'payout')->sum('amount');
        $pendingBalance = $netEarnings - $totalPayouts;
        
        $statementData = [
            'vendor' => $vendor,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'total_sales' => $totalSales,
            'total_commission' => $totalCommission,
            'net_earnings' => $netEarnings,
            'total_payouts' => $totalPayouts,
            'pending_balance' => $pendingBalance,
            'transactions' => $transactionList,
            'order_count' => $orders->count(),
            'average_order_value' => $orders->count() > 0 ? $totalSales / $orders->count() : 0,
            'statement_date' => now(),
            'statement_number' => $this->generateStatementNumber($vendor),
        ];
        
        // Generate PDF
        $this->generateStatementPDF($statementData, 'vendor');
        
        return $statementData;
    }
    
    /**
     * Generate reconciliation report
     */
    public function generateReconciliation(Carbon $date = null): array
    {
        $date = $date ?? Carbon::yesterday();
        
        // Get all payments for the day
        $payments = Payment::whereDate('paid_at', $date)
            ->with(['order', 'buyer', 'vendor'])
            ->get();
        
        // Group by payment method
        $byMethod = $payments->groupBy('payment_method');
        
        // Calculate totals
        $reconciliation = [];
        
        foreach ($byMethod as $method => $methodPayments) {
            $reconciliation[$method] = [
                'count' => $methodPayments->count(),
                'total_amount' => $methodPayments->sum('amount'),
                'total_fees' => $methodPayments->sum(function ($payment) {
                    $fees = app(PaymentProcessor::class)->calculateFees(
                        $payment->amount,
                        $payment->payment_method
                    );
                    return $fees['payment_fee'];
                }),
                'payments' => $methodPayments,
            ];
        }
        
        // Stripe reconciliation
        if (isset($reconciliation['card']) || isset($reconciliation['au_becs_debit'])) {
            $stripeBalance = $this->getStripeBalance($date);
            $reconciliation['stripe_balance'] = $stripeBalance;
        }
        
        // Cash reconciliation
        if (isset($reconciliation['cash_on_delivery'])) {
            $cashCollected = Payment::where('payment_method', 'cash_on_delivery')
                ->whereDate('paid_at', $date)
                ->where('status', 'completed')
                ->sum('amount');
            
            $reconciliation['cash_collected'] = $cashCollected;
        }
        
        return [
            'date' => $date,
            'reconciliation' => $reconciliation,
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'generated_at' => now(),
        ];
    }
    
    /**
     * Calculate opening balance for a buyer
     */
    protected function calculateOpeningBalance(Buyer $buyer, Carbon $date): float
    {
        $previousInvoices = Invoice::where('buyer_id', $buyer->id)
            ->where('issue_date', '<', $date)
            ->where('status', '!=', 'void')
            ->sum('total_amount');
        
        $previousPayments = Payment::where('buyer_id', $buyer->id)
            ->where('created_at', '<', $date)
            ->where('status', 'completed')
            ->sum('amount');
        
        return $previousInvoices - $previousPayments;
    }
    
    /**
     * Get Stripe balance for reconciliation
     */
    protected function getStripeBalance(Carbon $date): array
    {
        try {
            $stripe = new \Stripe\StripeClient(config('stripe.secret'));
            
            $balanceTransactions = $stripe->balanceTransactions->all([
                'created' => [
                    'gte' => $date->startOfDay()->timestamp,
                    'lte' => $date->endOfDay()->timestamp,
                ],
                'limit' => 100,
            ]);
            
            $totalIn = 0;
            $totalOut = 0;
            $fees = 0;
            
            foreach ($balanceTransactions->data as $transaction) {
                if ($transaction->type === 'charge') {
                    $totalIn += $transaction->amount / 100;
                    $fees += $transaction->fee / 100;
                } elseif ($transaction->type === 'refund') {
                    $totalOut += abs($transaction->amount) / 100;
                }
            }
            
            return [
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'fees' => $fees,
                'net' => $totalIn - $totalOut - $fees,
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to fetch Stripe balance',
            ];
        }
    }
    
    /**
     * Generate statement PDF
     */
    protected function generateStatementPDF(array $statementData, string $type): string
    {
        $pdf = Pdf::loadView("invoices.statement-{$type}", $statementData);
        $pdf->setPaper('A4', 'portrait');
        
        $entity = $type === 'buyer' ? $statementData['buyer'] : $statementData['vendor'];
        $filename = "statements/{$type}/" . $entity->id . '/' . $statementData['statement_number'] . '.pdf';
        
        Storage::disk('public')->makeDirectory("statements/{$type}/" . $entity->id);
        
        $path = storage_path('app/public/' . $filename);
        $pdf->save($path);
        
        return $filename;
    }
    
    /**
     * Generate statement number
     */
    protected function generateStatementNumber($entity): string
    {
        $prefix = $entity instanceof Buyer ? 'BS' : 'VS';
        $yearMonth = Carbon::now()->format('Ym');
        $entityId = str_pad($entity->id, 5, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$yearMonth}-{$entityId}";
    }
    
    /**
     * Send statement by email
     */
    public function sendStatementByEmail(array $statementData, string $email): void
    {
        Mail::to($email)->send(new \App\Mail\StatementEmail($statementData));
    }
    
    /**
     * Generate batch statements
     */
    public function generateBatchStatements(string $type = 'buyer', Carbon $date = null): array
    {
        $date = $date ?? Carbon::now()->subMonth();
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();
        
        $results = [];
        
        if ($type === 'buyer') {
            $buyers = Buyer::whereHas('orders', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })->get();
            
            foreach ($buyers as $buyer) {
                try {
                    $statement = $this->generateBuyerStatement($buyer, $startDate, $endDate);
                    $results[] = [
                        'buyer_id' => $buyer->id,
                        'status' => 'success',
                        'statement_number' => $statement['statement_number'],
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'buyer_id' => $buyer->id,
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        } else {
            $vendors = Vendor::whereHas('orders', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })->get();
            
            foreach ($vendors as $vendor) {
                try {
                    $statement = $this->generateVendorStatement($vendor, $startDate, $endDate);
                    $results[] = [
                        'vendor_id' => $vendor->id,
                        'status' => 'success',
                        'statement_number' => $statement['statement_number'],
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'vendor_id' => $vendor->id,
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }
        
        return $results;
    }
}