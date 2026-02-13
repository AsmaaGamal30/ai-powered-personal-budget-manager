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

class GetBudgetRecommendationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    #[Test]
    public function recommendations_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/ai/recommendations');

        $response->assertUnauthorized();
    }

    #[Test]
    public function recommendations_endpoint_returns_all_categories_by_default(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '6000',
            'age' => '28',
        ]);

        $categories = Category::take(2)->get();
        foreach ($categories as $category) {
            $budget = Budget::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'amount' => 1000,
            ]);

            Stats::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'budget_id' => $budget->id,
                'amount' => 800,
                'date' => now(),
                'stats_type' => 'monthly',
            ]);
        }

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('getBudgetRecommendations')
            ->once()
            ->with($user, null)
            ->andReturn([
                'recommendations' => 'Based on your salary of $6000, I recommend allocating $1200 to savings, $1500 to housing, and $800 to groceries.',
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/recommendations');

        $response->assertOk()
            ->assertJsonStructure([
                'recommendations',
            ]);
    }

    #[Test]
    public function recommendations_endpoint_accepts_specific_category(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '5000',
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 800,
        ]);

        Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 900,
            'date' => now(),
            'stats_type' => 'monthly',
        ]);

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('getBudgetRecommendations')
            ->once()
            ->with($user, $category->id)
            ->andReturn([
                'recommendations' => "For the {$category->name} category, you've exceeded your budget. Consider increasing it to $1000.",
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/recommendations?category_id=' . $category->id);

        $response->assertOk()
            ->assertJsonStructure([
                'recommendations',
            ]);
    }

    #[Test]
    public function recommendations_considers_family_provider_status(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '9000',
            'age' => '42',
            'is_single' => false,
            'is_family_provider' => true,
            'family_members_count' => 5,
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
        $aiService->shouldReceive('getBudgetRecommendations')
            ->once()
            ->andReturn([
                'recommendations' => 'As a family provider for 5 members, prioritize essential categories like groceries and healthcare.',
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/recommendations');

        $response->assertOk()
            ->assertJsonStructure([
                'recommendations',
            ]);
    }

    #[Test]
    public function recommendations_works_with_no_existing_budgets(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '4500',
        ]);

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('getBudgetRecommendations')
            ->once()
            ->andReturn([
                'recommendations' => 'Start by setting up budgets for essential categories like housing, groceries, and transportation.',
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/recommendations');

        $response->assertOk()
            ->assertJsonStructure([
                'recommendations',
            ]);
    }

    #[Test]
    public function recommendations_handles_single_person_budget(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '3500',
            'age' => '24',
            'is_single' => true,
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
            'budget_id' => $budget->id,
            'amount' => 400,
            'date' => now(),
            'stats_type' => 'monthly',
        ]);

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('getBudgetRecommendations')
            ->once()
            ->andReturn([
                'recommendations' => 'As a young single adult, focus on building emergency savings while keeping living costs manageable.',
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/recommendations');

        $response->assertOk()
            ->assertJsonStructure([
                'recommendations',
            ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
