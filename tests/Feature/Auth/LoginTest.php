<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;


    public User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(
            [
                'email' => 'test@test.com',
                'otp' => '12345'
            ]
        );
    }

    #[Test]
    public function user_can_login_successfully()
    {
        $data = [
            'email' => 'test@test.com',
            'otp' => '12345'
        ];


    }
}