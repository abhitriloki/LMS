<?php

namespace Tests\Unit\Rules;

use App\Rules\SecurePassword;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SecurePasswordTest extends TestCase
{
    public function test_password_requires_minimum_length(): void
    {
        $validator = Validator::make(
            ['password' => 'Short1!'],
            ['password' => [new SecurePassword()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('at least', $validator->errors()->first('password'));
    }

    public function test_password_requires_uppercase(): void
    {
        $validator = Validator::make(
            ['password' => 'lowercase123!'],
            ['password' => [new SecurePassword()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('uppercase', $validator->errors()->first('password'));
    }

    public function test_password_requires_lowercase(): void
    {
        $validator = Validator::make(
            ['password' => 'UPPERCASE123!'],
            ['password' => [new SecurePassword()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('lowercase', $validator->errors()->first('password'));
    }

    public function test_password_requires_number(): void
    {
        $validator = Validator::make(
            ['password' => 'NoNumbers!'],
            ['password' => [new SecurePassword()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('number', $validator->errors()->first('password'));
    }

    public function test_password_requires_special_character(): void
    {
        $validator = Validator::make(
            ['password' => 'NoSpecial123'],
            ['password' => [new SecurePassword()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('special character', $validator->errors()->first('password'));
    }

    public function test_password_rejects_common_passwords(): void
    {
        $commonPasswords = ['password', 'password123', '12345678', 'qwerty'];

        foreach ($commonPasswords as $password) {
            $validator = Validator::make(
                ['password' => $password],
                ['password' => [new SecurePassword()]]
            );

            $this->assertTrue($validator->fails());
        }
    }

    public function test_password_accepts_strong_password(): void
    {
        $validator = Validator::make(
            ['password' => 'StrongP@ssw0rd!'],
            ['password' => [new SecurePassword()]]
        );

        $this->assertFalse($validator->fails());
    }
}
