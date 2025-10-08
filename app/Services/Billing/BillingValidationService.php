<?php

namespace App\Services\Billing;

use App\Models\Buyer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class BillingValidationService
{
    /**
     * Validate billing information
     */
    public function validateBillingInformation(array $data): array
    {
        $errors = [];
        $warnings = [];
        
        // Validate payment method specific requirements
        switch ($data['payment_method']) {
            case 'card':
                $cardValidation = $this->validateCardDetails($data);
                if (!$cardValidation['valid']) {
                    $errors = array_merge($errors, $cardValidation['errors']);
                }
                break;
                
            case 'au_becs_debit':
                $bankValidation = $this->validateBankDetails($data);
                if (!$bankValidation['valid']) {
                    $errors = array_merge($errors, $bankValidation['errors']);
                }
                break;
                
            case 'credit_terms':
                $creditValidation = $this->validateCreditTerms($data);
                if (!$creditValidation['valid']) {
                    $errors = array_merge($errors, $creditValidation['errors']);
                }
                if (!empty($creditValidation['warnings'])) {
                    $warnings = array_merge($warnings, $creditValidation['warnings']);
                }
                break;
                
            case 'afterpay_clearpay':
                $afterpayValidation = $this->validateAfterpay($data);
                if (!$afterpayValidation['valid']) {
                    $errors = array_merge($errors, $afterpayValidation['errors']);
                }
                break;
        }
        
        // Validate delivery address if delivery selected
        if ($data['fulfillment_type'] === 'delivery') {
            $addressValidation = $this->validateDeliveryAddress($data);
            if (!$addressValidation['valid']) {
                $errors = array_merge($errors, $addressValidation['errors']);
            }
        }
        
        // Validate billing address if required
        if ($this->requiresBillingAddress($data['payment_method'])) {
            $billingValidation = $this->validateBillingAddress($data);
            if (!$billingValidation['valid']) {
                $errors = array_merge($errors, $billingValidation['errors']);
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Validate card details
     */
    protected function validateCardDetails(array $data): array
    {
        $errors = [];
        
        // Skip validation if using saved payment method
        if (isset($data['payment_method_id']) && $data['payment_method_id'] !== 'new') {
            return ['valid' => true, 'errors' => []];
        }
        
        // Validate card number
        if (empty($data['card_number'])) {
            $errors['card_number'] = 'Card number is required.';
        } else {
            $cardNumber = str_replace(' ', '', $data['card_number']);
            if (!$this->isValidCardNumber($cardNumber)) {
                $errors['card_number'] = 'Invalid card number.';
            }
        }
        
        // Validate cardholder name
        if (empty($data['card_name'])) {
            $errors['card_name'] = 'Cardholder name is required.';
        } elseif (strlen($data['card_name']) < 3) {
            $errors['card_name'] = 'Cardholder name must be at least 3 characters.';
        }
        
        // Validate expiry date
        if (empty($data['card_expiry'])) {
            $errors['card_expiry'] = 'Card expiry date is required.';
        } else {
            $expiry = $this->parseCardExpiry($data['card_expiry']);
            if (!$expiry || $expiry['expired']) {
                $errors['card_expiry'] = 'Invalid or expired card expiry date.';
            }
        }
        
        // Validate CVC
        if (empty($data['card_cvc'])) {
            $errors['card_cvc'] = 'Card CVC is required.';
        } elseif (!preg_match('/^\d{3,4}$/', $data['card_cvc'])) {
            $errors['card_cvc'] = 'Invalid CVC. Must be 3 or 4 digits.';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate bank account details
     */
    protected function validateBankDetails(array $data): array
    {
        $errors = [];
        
        // Skip validation if using saved payment method
        if (isset($data['payment_method_id']) && $data['payment_method_id'] !== 'new') {
            return ['valid' => true, 'errors' => []];
        }
        
        // Validate account name
        if (empty($data['account_name'])) {
            $errors['account_name'] = 'Account name is required.';
        } elseif (strlen($data['account_name']) < 3) {
            $errors['account_name'] = 'Account name must be at least 3 characters.';
        }
        
        // Validate BSB
        if (empty($data['bsb'])) {
            $errors['bsb'] = 'BSB is required.';
        } elseif (!preg_match('/^\d{6}$/', str_replace('-', '', $data['bsb']))) {
            $errors['bsb'] = 'Invalid BSB. Must be 6 digits.';
        } elseif (!$this->isValidBSB($data['bsb'])) {
            $errors['bsb'] = 'Invalid BSB number.';
        }
        
        // Validate account number
        if (empty($data['account_number'])) {
            $errors['account_number'] = 'Account number is required.';
        } elseif (!preg_match('/^\d{6,9}$/', $data['account_number'])) {
            $errors['account_number'] = 'Invalid account number. Must be 6-9 digits.';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate credit terms
     */
    protected function validateCreditTerms(array $data): array
    {
        $errors = [];
        $warnings = [];
        
        // Check if buyer has approved credit
        $buyerId = auth()->id();
        $buyer = Buyer::find($buyerId);
        
        if (!$buyer || !$buyer->credit_approved) {
            $errors['credit_terms'] = 'Credit terms are not available for your account. Please contact support.';
            return ['valid' => false, 'errors' => $errors, 'warnings' => []];
        }
        
        // Check credit limit
        if (isset($data['total_amount']) && $buyer->credit_limit) {
            $outstandingCredit = $this->getOutstandingCredit($buyer);
            $availableCredit = $buyer->credit_limit - $outstandingCredit;
            
            if ($data['total_amount'] > $availableCredit) {
                $errors['credit_terms'] = sprintf(
                    'Insufficient credit limit. Available: $%s, Required: $%s',
                    number_format($availableCredit, 2),
                    number_format($data['total_amount'], 2)
                );
            } elseif ($data['total_amount'] > ($availableCredit * 0.8)) {
                $warnings['credit_limit'] = sprintf(
                    'This order will use %s%% of your available credit limit.',
                    round(($data['total_amount'] / $availableCredit) * 100)
                );
            }
        }
        
        // Validate credit terms days
        if (!in_array($data['credit_terms_days'] ?? 7, [7, 14, 30, 60])) {
            $errors['credit_terms_days'] = 'Invalid credit terms period selected.';
        }
        
        // Check for overdue payments
        if ($this->hasOverduePayments($buyer)) {
            $errors['credit_terms'] = 'You have overdue payments. Please settle outstanding invoices before using credit terms.';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Validate Afterpay
     */
    protected function validateAfterpay(array $data): array
    {
        $errors = [];
        
        // Check order amount limits
        if (isset($data['total_amount'])) {
            if ($data['total_amount'] < 35) {
                $errors['afterpay'] = 'Afterpay requires a minimum order of $35.';
            } elseif ($data['total_amount'] > 2000) {
                $errors['afterpay'] = 'Afterpay is only available for orders up to $2,000.';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate delivery address
     */
    protected function validateDeliveryAddress(array $data): array
    {
        $errors = [];
        
        // Check if using existing address or creating new
        if (empty($data['delivery_address_id']) || $data['delivery_address_id'] === 'new') {
            // Validate new address fields
            if (empty($data['new_delivery_address']['line1'])) {
                $errors['new_delivery_address.line1'] = 'Street address is required for delivery.';
            }
            
            if (empty($data['new_delivery_address']['suburb'])) {
                $errors['new_delivery_address.suburb'] = 'Suburb is required for delivery.';
            }
            
            if (empty($data['new_delivery_address']['state'])) {
                $errors['new_delivery_address.state'] = 'State is required for delivery.';
            }
            
            if (empty($data['new_delivery_address']['postcode'])) {
                $errors['new_delivery_address.postcode'] = 'Postcode is required for delivery.';
            } elseif (!preg_match('/^\d{4}$/', $data['new_delivery_address']['postcode'])) {
                $errors['new_delivery_address.postcode'] = 'Invalid postcode. Must be 4 digits.';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate billing address
     */
    protected function validateBillingAddress(array $data): array
    {
        $errors = [];
        
        // Check if using existing address or creating new
        if (empty($data['billing_address_id']) || $data['billing_address_id'] === 'new') {
            // Validate new address fields if provided
            if (isset($data['new_billing_address'])) {
                if (empty($data['new_billing_address']['line1'])) {
                    $errors['new_billing_address.line1'] = 'Street address is required for billing.';
                }
                
                if (empty($data['new_billing_address']['suburb'])) {
                    $errors['new_billing_address.suburb'] = 'Suburb is required for billing.';
                }
                
                if (empty($data['new_billing_address']['state'])) {
                    $errors['new_billing_address.state'] = 'State is required for billing.';
                }
                
                if (empty($data['new_billing_address']['postcode'])) {
                    $errors['new_billing_address.postcode'] = 'Postcode is required for billing.';
                } elseif (!preg_match('/^\d{4}$/', $data['new_billing_address']['postcode'])) {
                    $errors['new_billing_address.postcode'] = 'Invalid postcode. Must be 4 digits.';
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Check if payment method requires billing address
     */
    protected function requiresBillingAddress(string $paymentMethod): bool
    {
        return in_array($paymentMethod, ['card', 'afterpay_clearpay']);
    }
    
    /**
     * Validate card number using Luhn algorithm
     */
    protected function isValidCardNumber(string $cardNumber): bool
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        
        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            return false;
        }
        
        $sum = 0;
        $isEven = false;
        
        for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
            $digit = (int)$cardNumber[$i];
            
            if ($isEven) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $sum += $digit;
            $isEven = !$isEven;
        }
        
        return ($sum % 10) === 0;
    }
    
    /**
     * Parse and validate card expiry date
     */
    protected function parseCardExpiry(string $expiry): ?array
    {
        if (!preg_match('/^(\d{2})\/(\d{2})$/', $expiry, $matches)) {
            return null;
        }
        
        $month = (int)$matches[1];
        $year = 2000 + (int)$matches[2];
        
        if ($month < 1 || $month > 12) {
            return null;
        }
        
        $expiryDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        
        return [
            'month' => $month,
            'year' => $year,
            'expired' => $expiryDate->isPast()
        ];
    }
    
    /**
     * Validate Australian BSB number
     */
    protected function isValidBSB(string $bsb): bool
    {
        $bsb = str_replace('-', '', $bsb);
        
        // List of valid BSB prefixes for major Australian banks
        $validPrefixes = [
            '01', '03', '06', '08', '11', '12', '13', '14', '15', '16', 
            '18', '19', '20', '21', '22', '23', '24', '25', '26', '30',
            '31', '32', '33', '34', '35', '36', '37', '38', '40', '41',
            '42', '43', '44', '45', '46', '47', '48', '50', '51', '52',
            '53', '54', '55', '57', '58', '59', '60', '61', '62', '63',
            '64', '65', '66', '67', '68', '69', '70', '71', '72', '73',
            '74', '75', '76', '77', '78', '80', '81', '82', '83', '84',
            '85', '86', '87', '88', '90', '91', '92', '93', '94', '95'
        ];
        
        $prefix = substr($bsb, 0, 2);
        return in_array($prefix, $validPrefixes);
    }
    
    /**
     * Get outstanding credit for buyer
     */
    protected function getOutstandingCredit(Buyer $buyer): float
    {
        return Payment::where('buyer_id', $buyer->id)
            ->where('payment_method', 'credit_terms')
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('amount');
    }
    
    /**
     * Check if buyer has overdue payments
     */
    protected function hasOverduePayments(Buyer $buyer): bool
    {
        return Payment::where('buyer_id', $buyer->id)
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->exists();
    }
    
    /**
     * Validate fraud indicators
     */
    public function validateFraudIndicators(array $data, Buyer $buyer): array
    {
        $riskScore = 0;
        $flags = [];
        
        // Check for rapid successive orders
        $recentOrders = Order::where('buyer_id', $buyer->id)
            ->where('created_at', '>', now()->subHours(24))
            ->count();
        
        if ($recentOrders > 5) {
            $riskScore += 20;
            $flags[] = 'Multiple orders in 24 hours';
        }
        
        // Check for unusually high order amount
        $avgOrderAmount = Order::where('buyer_id', $buyer->id)
            ->where('status', '!=', 'cancelled')
            ->avg('total_amount') ?? 0;
        
        if (isset($data['total_amount']) && $avgOrderAmount > 0) {
            if ($data['total_amount'] > ($avgOrderAmount * 3)) {
                $riskScore += 30;
                $flags[] = 'Order amount significantly higher than average';
            }
        }
        
        // Check for new payment method on high-value order
        if (isset($data['total_amount']) && $data['total_amount'] > 1000) {
            if (!isset($data['payment_method_id']) || $data['payment_method_id'] === 'new') {
                $riskScore += 15;
                $flags[] = 'New payment method on high-value order';
            }
        }
        
        // Check for mismatched addresses
        if (isset($data['new_delivery_address']) && isset($data['new_billing_address'])) {
            if ($data['new_delivery_address']['postcode'] !== $data['new_billing_address']['postcode']) {
                $riskScore += 10;
                $flags[] = 'Different billing and delivery postcodes';
            }
        }
        
        // Check account age
        $accountAge = $buyer->created_at->diffInDays(now());
        if ($accountAge < 7 && isset($data['total_amount']) && $data['total_amount'] > 500) {
            $riskScore += 25;
            $flags[] = 'New account with high-value order';
        }
        
        return [
            'risk_score' => $riskScore,
            'risk_level' => $this->getRiskLevel($riskScore),
            'flags' => $flags,
            'requires_review' => $riskScore >= 50
        ];
    }
    
    /**
     * Get risk level based on score
     */
    protected function getRiskLevel(int $score): string
    {
        if ($score < 20) return 'low';
        if ($score < 50) return 'medium';
        if ($score < 80) return 'high';
        return 'very_high';
    }
}