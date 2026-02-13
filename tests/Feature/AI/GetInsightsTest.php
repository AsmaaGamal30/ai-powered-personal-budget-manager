<?php

namespace Tests\Feature\AI;

use App\Models\User;
use App\Models\Category;
use App\Models\Budget;
use App\Models\Stats;
use App\Services\AIAssistantService;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class GetInsightsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    #[Test]
    public function insights_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/ai/insights');

        $response->assertUnauthorized();
    }

    #[Test]
    public function insights_endpoint_returns_monthly_insights_by_default(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '6000',
            'age' => '35',
            'gender' => 'female',
            'is_single' => true,
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1500,
        ]);

        Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 800,
            'date' => now(),
            'stats_type' => 'monthly',
        ]);

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('getInsights')
            ->once()
            ->with($user, 'monthly', now()->format('Y-m-d'))
            ->andReturn([
                'period' => 'monthly',
                'insights' => 'You are doing well managing your budget. Keep up the good work.',
                'generated_at' => now()->toIso8601String(),
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/insights');

        $response->assertOk()
            ->assertJsonStructure([
                'period',
                'insights',
                'generated_at',
            ])
            ->assertJson([
                'period' => 'monthly',
            ]);
    }

    #[Test]
    public function insights_endpoint_accepts_custom_period(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '7000',
        ]);

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('getInsights')
            ->once()
            ->with($user, 'weekly', now()->format('Y-m-d'))
            ->andReturn([
                'period' => 'weekly',
                'insights' => 'This week you spent more on dining out.',
                'generated_at' => now()->toIso8601String(),
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/insights?period=weekly');

        $response->assertOk()
            ->assertJson([
                'period' => 'weekly',
            ]);
    }

    #[Test]
    public function insights_endpoint_accepts_custom_date(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '5500',
        ]);

        $customDate = '2026-01-15';

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('getInsights')
            ->once()
            ->with($user, 'monthly', $customDate)
            ->andReturn([
                'period' => 'monthly',
                'insights' => 'For January 2026, your spending was high.',
                'generated_at' => now()->toIso8601String(),
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/insights?date=' . $customDate);

        $response->assertOk()
            ->assertJsonStructure([
                'period',
                'insights',
                'generated_at',
            ]);
    }

    #[Test]
    public function insights_endpoint_works_with_family_provider(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '8000',
            'age' => '40',
            'is_single' => false,
            'is_family_provider' => true,
            'family_members_count' => 4,
        ]);

        $categories = Category::take(3)->get();
        foreach ($categories as $category) {
            $budget = Budget::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'amount' => 2000,
            ]);

            Stats::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'budget_id' => $budget->id,
                'amount' => 1500,
                'date' => now(),
                'stats_type' => 'monthly',
            ]);
        }

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('getInsights')
            ->once()
            ->andReturn([
                'period' => 'monthly',
                'insights' => 'As a family provider with 4 members, you are managing well.',
                'generated_at' => now()->toIso8601String(),
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/insights');

        $response->assertOk()
            ->assertJsonStructure([
                'period',
                'insights',
                'generated_at',
            ]);
    }

    #[Test]
    public function insights_endpoint_works_for_quarterly_period(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('getInsights')
            ->once()
            ->with($user, 'quarterly', now()->format('Y-m-d'))
            ->andReturn([
                'period' => 'quarterly',
                'insights' => 'Over the quarter, your spending has been consistent.',
                'generated_at' => now()->toIso8601String(),
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/insights?period=quarterly');

        $response->assertOk()
            ->assertJson([
                'period' => 'quarterly',
            ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}