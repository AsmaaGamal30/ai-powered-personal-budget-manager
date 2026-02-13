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

class AnalyzeAnomaliesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    #[Test]
    public function anomalies_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/ai/anomalies');

        $response->assertUnauthorized();
    }

    #[Test]
    public function anomalies_endpoint_returns_monthly_analysis_by_default(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '5000',
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 500,
        ]);

        Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 100,
            'date' => now()->subDays(5),
            'budget_id' => $budget->id,
            'stats_type' => 'daily',
        ]);

        Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 800,
            'date' => now()->subDays(2),
            'budget_id' => $budget->id,
            'stats_type' => 'daily',
        ]);

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('analyzeAnomalies')
            ->once()
            ->with($user, 'monthly')
            ->andReturn([
                'anomalies' => 'Detected unusual spike in spending on ' . now()->subDays(2)->format('Y-m-d') . ' with $800 spent, significantly higher than typical daily average.',
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/anomalies');

        $response->assertOk()
            ->assertJsonStructure([
                'anomalies',
            ]);
    }

    #[Test]
    public function anomalies_endpoint_accepts_custom_period(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '6000',
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 200,
        ]);

        Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 50,
            'date' => now()->subDays(1),
            'budget_id' => $budget->id,
            'stats_type' => 'daily',
        ]);

        Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 300,
            'date' => now(),
            'budget_id' => $budget->id,
            'stats_type' => 'daily',
        ]);

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('analyzeAnomalies')
            ->once()
            ->with($user, 'weekly')
            ->andReturn([
                'anomalies' => 'This week shows an unusual spending pattern with a 6x increase compared to previous days.',
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/anomalies?period=weekly');

        $response->assertOk()
            ->assertJsonStructure([
                'anomalies',
            ]);
    }

    #[Test]
    public function anomalies_endpoint_handles_multiple_category_spikes(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '7000',
            'is_family_provider' => true,
            'family_members_count' => 3,
        ]);

        $categories = Category::take(2)->get();
        foreach ($categories as $index => $category) {
            $budget = Budget::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'amount' => 1000,
            ]);

            Stats::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'amount' => 200,
                'date' => now()->subDays(10),
                'budget_id' => $budget->id,
                'stats_type' => 'daily',
            ]);

            Stats::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'amount' => 1500,
                'date' => now()->subDays(1),
                'budget_id' => $budget->id,
                'stats_type' => 'daily',
            ]);
        }

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('analyzeAnomalies')
            ->once()
            ->andReturn([
                'anomalies' => 'Multiple categories show unusual spikes. This is particularly concerning given your family responsibilities.',
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/anomalies');

        $response->assertOk()
            ->assertJsonStructure([
                'anomalies',
            ]);
    }

    #[Test]
    public function anomalies_endpoint_handles_no_anomalies(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '4500',
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 600,
        ]);

        for ($i = 0; $i < 5; $i++) {
            Stats::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'budget_id' => $budget->id,
                'amount' => 100,
                'date' => now()->subDays($i),
                'stats_type' => 'daily',
            ]);
        }

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('analyzeAnomalies')
            ->once()
            ->andReturn([
                'anomalies' => 'No significant anomalies detected. Your spending pattern is consistent and predictable.',
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/anomalies');

        $response->assertOk()
            ->assertJsonStructure([
                'anomalies',
            ]);
    }

    #[Test]
    public function anomalies_endpoint_works_with_quarterly_period(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 3000,
        ]);

        Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 2500,
            'date' => now()->subMonths(2),
            'budget_id' => $budget->id,
            'stats_type' => 'quarterly',
        ]);

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('analyzeAnomalies')
            ->once()
            ->with($user, 'quarterly')
            ->andReturn([
                'anomalies' => 'Quarterly analysis shows significant spending in the first month with minimal activity since.',
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/anomalies?period=quarterly');

        $response->assertOk()
            ->assertJsonStructure([
                'anomalies',
            ]);
    }

    #[Test]
    public function anomalies_endpoint_handles_budget_overrun(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '5000',
            'age' => '30',
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 500,
        ]);

        Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 700,
            'date' => now(),
            'budget_id' => $budget->id,
            'stats_type' => 'daily',
        ]);

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('analyzeAnomalies')
            ->once()
            ->andReturn([
                'anomalies' => 'Budget overrun detected: You have spent $700 against a budget of $500, exceeding it by $200.',
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/anomalies');

        $response->assertOk()
            ->assertJsonStructure([
                'anomalies',
            ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}