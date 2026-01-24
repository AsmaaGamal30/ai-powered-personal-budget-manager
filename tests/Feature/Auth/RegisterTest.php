<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    #[Test]
    public function user_can_register_successfully(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@test.com'
        ];

        $response = $this->postJson(route('register'), $data);

        $response->assertCreated();

        $this->assertTrue(Cache::has('otp_' . $data['email']));

    }

    #[Test]
    public function registration_fails_with_existing_email(): void
    {
        User::factory()->create([
            'email' => 'existing@test.com'
        ]);
        $data = [
            'name' => 'Existing User',
            'email' => 'existing@test.com'
        ];
        $response = $this->postJson(route('register'), $data);

        $response->assertUnprocessable();
    }


}
