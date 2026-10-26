<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Login step 2 (OTP): a right OTP with a wrong password or a disabled account says so, instead of
 * "Please enter a valid 4-digit OTP." (customLogin itself ends with exit(), so step 1 is not called here).
 */
class LoginOtpMessageTest extends TestCase
{
    use DatabaseTransactions;

    private string $email;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://127.0.0.1');   // "localhost" is a tenants-table company on its own connection
        $this->email = uniqid('otp', true) . '@example.test';
        DB::table('users')->insert(['name' => 'OTP Test', 'email' => $this->email, 'password' => bcrypt('Right#Pass1'),
            'permissions_id' => DB::table('permissions')->where('name', 'Telecaller')->value('id'), 'status' => 1,
            'login_otp' => '7468', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function otp(string $otp, string $password)
    {
        return json_decode($this->post(route('adminValidateOtp'), ['email' => $this->email, 'password' => $password, 'otp' => $otp])->getContent(), true);
    }

    public function test_right_otp_with_wrong_password_says_wrong_password()
    {
        $this->assertSame('Wrong Password', $this->otp('7468', '123456')['error'] ?? null);
    }

    public function test_right_otp_on_a_disabled_account_says_account_disabled()
    {
        DB::table('users')->where('email', $this->email)->update(['status' => 0]);
        $this->assertSame('Account Disabled', $this->otp('7468', 'Right#Pass1')['error'] ?? null);
    }

    public function test_wrong_otp_still_says_otp_and_the_right_one_logs_in()
    {
        $this->assertSame('Please enter a valid 4-digit OTP.', $this->otp('1111', 'Right#Pass1')['error'] ?? null);
        $this->assertSame('Logged in successfully', $this->otp('7468', 'Right#Pass1')['success'] ?? null);
    }
}
