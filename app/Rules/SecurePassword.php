<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SecurePassword implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $policy = config('security.password_policy');
        
        // Check minimum length
        if (strlen($value) < $policy['min_length']) {
            $fail("The {$attribute} must be at least {$policy['min_length']} characters.");
            return;
        }

        // Check for uppercase letter
        if ($policy['require_uppercase'] && !preg_match('/[A-Z]/', $value)) {
            $fail("The {$attribute} must contain at least one uppercase letter.");
            return;
        }

        // Check for lowercase letter
        if ($policy['require_lowercase'] && !preg_match('/[a-z]/', $value)) {
            $fail("The {$attribute} must contain at least one lowercase letter.");
            return;
        }

        // Check for number
        if ($policy['require_numbers'] && !preg_match('/[0-9]/', $value)) {
            $fail("The {$attribute} must contain at least one number.");
            return;
        }

        // Check for special character
        if ($policy['require_special_chars'] && !preg_match('/[^A-Za-z0-9]/', $value)) {
            $fail("The {$attribute} must contain at least one special character.");
            return;
        }

        // Check for common weak passwords
        $weakPasswords = [
            'password', 'password123', '12345678', 'qwerty', 'abc123',
            'letmein', 'welcome', 'monkey', '1234567890', 'admin'
        ];

        if (in_array(strtolower($value), $weakPasswords)) {
            $fail("The {$attribute} is too common. Please choose a stronger password.");
            return;
        }
    }
}
