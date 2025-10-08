<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpleBuyerRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            // Basic Information
            'first_name' => 'required|string|max:100|regex:/^[a-zA-Z\s\'-]+$/',
            'last_name' => 'required|string|max:100|regex:/^[a-zA-Z\s\'-]+$/',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('buyers', 'email'),
                Rule::unique('business_users', 'email')
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^[\d\s\+\-\(\)]+$/',
                function ($attribute, $value, $fail) {
                    $cleanPhone = preg_replace('/\D/', '', $value);
                    if (!$this->isValidPhoneNumber($cleanPhone)) {
                        $fail('Please enter a valid phone number.');
                    }
                }
            ],
            
            // Account Type
            'buyer_type' => 'required|in:individual,business',
            
            // Password
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).*$/'
            ],
            'password_confirmation' => 'required|string',
            
            // Terms
            'terms_accepted' => 'required|accepted',
        ];

        // Business-specific fields (optional for individual accounts)
        if ($this->input('buyer_type') === 'business') {
            $rules['company_name'] = 'required|string|max:255';
            $rules['abn'] = [
                'required',
                'string',
                'regex:/^[\d\s]{11,15}$/', // Allow 11 digits with optional spaces
                function ($attribute, $value, $fail) {
                    // Remove all non-digit characters and check if it's exactly 11 digits
                    $cleanAbn = preg_replace('/\D/', '', $value);
                    if (strlen($cleanAbn) !== 11) {
                        $fail('The ABN must be exactly 11 digits.');
                    }
                    
                    // Validate ABN checksum
                    if (!$this->validateABNChecksum($cleanAbn)) {
                        $fail('Invalid ABN. Please check the number and try again.');
                    }
                }
            ];
        } else {
            // For individual accounts, these fields are optional
            $rules['company_name'] = 'nullable|string|max:255';
            $rules['abn'] = [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $cleanAbn = preg_replace('/\D/', '', $value);
                        if (strlen($cleanAbn) !== 11) {
                            $fail('The ABN must be exactly 11 digits.');
                        }
                        
                        if (!$this->validateABNChecksum($cleanAbn)) {
                            $fail('Invalid ABN. Please check the number and try again.');
                        }
                    }
                }
            ];
        }

        return $rules;
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'first_name.regex' => 'First name can only contain letters, spaces, apostrophes, and hyphens.',
            'last_name.required' => 'Last name is required.',
            'last_name.regex' => 'Last name can only contain letters, spaces, apostrophes, and hyphens.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered. Please use a different email or try logging in.',
            'phone.required' => 'Phone number is required.',
            'buyer_type.required' => 'Please select account type (individual or business).',
            'buyer_type.in' => 'Please select a valid account type.',
            'company_name.required' => 'Company name is required for business accounts.',
            'abn.required' => 'ABN is required for business accounts.',
            'abn.regex' => 'Please enter a valid 11-digit ABN.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
            'terms_accepted.required' => 'You must accept the terms and conditions.',
            'terms_accepted.accepted' => 'You must accept the terms and conditions to continue.',
        ];
    }

    /**
     * Validate ABN checksum
     */
    protected function validateABNChecksum(string $abn): bool
    {
        if (strlen($abn) !== 11 || !ctype_digit($abn)) {
            return false;
        }

        $weights = [10, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19];
        $sum = 0;

        // Apply weights
        for ($i = 0; $i < 11; $i++) {
            $digit = (int) $abn[$i];
            
            // For the first digit, subtract 1 before applying weight
            if ($i === 0) {
                $digit -= 1;
            }
            
            $sum += $digit * $weights[$i];
        }

        // Check if sum is divisible by 89
        return $sum % 89 === 0;
    }

    /**
     * Validate phone number (supports international formats)
     */
    protected function isValidPhoneNumber(string $phone): bool
    {
        // Remove any remaining non-digits
        $phone = preg_replace('/\D/', '', $phone);
        
        // Check for minimum and maximum length
        $length = strlen($phone);
        
        // Valid phone number lengths (6-15 digits internationally)
        if ($length < 6 || $length > 15) {
            return false;
        }
        
        // Check for Australian numbers
        if ($this->isAustralianNumber($phone)) {
            return $this->isValidAustralianPhone($phone);
        }
        
        // Allow other international formats
        return true;
    }

    /**
     * Check if number appears to be Australian
     */
    protected function isAustralianNumber(string $phone): bool
    {
        // Check for Australian patterns
        return (strlen($phone) === 10 && $phone[0] === '0') ||
               (strlen($phone) === 11 && substr($phone, 0, 2) === '61') ||
               (strlen($phone) === 9);
    }

    /**
     * Validate Australian phone number
     */
    protected function isValidAustralianPhone(string $phone): bool
    {
        // Valid Australian formats:
        // 10 digits starting with 0 (04XX XXX XXX or 0X XXXX XXXX)
        // 11 digits starting with 61 (country code)
        // 9 digits (without leading 0)
        
        if (strlen($phone) === 10 && $phone[0] === '0') {
            return true;
        } elseif (strlen($phone) === 11 && substr($phone, 0, 2) === '61') {
            return true;
        } elseif (strlen($phone) === 9) {
            return true;
        }
        
        return false;
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Clean ABN if provided
        if ($this->has('abn') && !empty($this->abn)) {
            $this->merge([
                'abn' => preg_replace('/\D/', '', $this->abn)
            ]);
        }

        // Clean phone number
        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/[^\d+]/', '', $this->phone)
            ]);
        }

        // Convert 'on' value for terms_accepted to boolean true
        if ($this->has('terms_accepted') && $this->terms_accepted === 'on') {
            $this->merge([
                'terms_accepted' => true
            ]);
        }

        // Ensure buyer_type has a default value if not provided
        if (!$this->has('buyer_type')) {
            $this->merge([
                'buyer_type' => 'individual'
            ]);
        }

        // Trim string fields
        $fieldsToTrim = ['first_name', 'last_name', 'email', 'company_name'];
        foreach ($fieldsToTrim as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => trim($this->input($field))
                ]);
            }
        }
    }
}