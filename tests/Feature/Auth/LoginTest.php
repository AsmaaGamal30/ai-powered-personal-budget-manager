<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;
    public User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->user = User::factory()->create([
            'email' => 'test@test.com',
        ]);

        Cache::put('otp_' . $this->user->email, '12345', now()->addMinutes(10));
    }

    #[Test]
    public function user_can_login_successfully(): void
    {
        $data = [
            'email' => 'test@test.com',
            'otp' => '12345',
        ];

        $response = $this->postJson(route('login'), $data);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token',
            'user',
        ]);
    }

    #[Test]
    public function user_cannot_login_with_invalid_otp(): void
    {
        $data = [
            'email' => 'test@test.com',
            'otp' => '54321',
        ];

        $response = $this->postJson(route('login'), $data);
        $response->assertUnauthorized();
    }


    #[Test]
    public function user_cannot_login_with_nonexistent_email(): void
    {
        $data = [
            'email' => 'nonexistent@test.com',
            'otp' => '12345',
        ];

        $response = $this->postJson(route('login'), $data);
        $response->assertUnprocessable();
    }

}
