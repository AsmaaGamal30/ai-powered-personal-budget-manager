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

class GetSavingsSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    #[Test]
    public function savings_suggestions_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/ai/savings-suggestions');

        $response->assertUnauthorized();
    }

    #[Test]
    public function savings_suggestions_endpoint_returns_general_suggestions_without_target(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '6000',
            'age' => '32',
        ]);

        $categories = Category::take(3)->get();
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
        $aiService->shouldReceive('getSavingsSuggestions')
            ->once()
            ->with($user, null)
            ->andReturn([
                'suggestions' => 'You can save approximately $600 per month by reducing discretionary spending.',
                'target_amount' => null,
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/savings-suggestions');

        $response->assertOk()
            ->assertJsonStructure([
                'suggestions',
                'target_amount',
            ]);
    }

    #[Test]
    public function savings_suggestions_endpoint_accepts_target_amount(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '7000',
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
            'amount' => 1200,
            'date' => now(),
            'stats_type' => 'monthly',
        ]);

        $targetAmount = 1000;

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('getSavingsSuggestions')
            ->once()
            ->with($user, $targetAmount)
            ->andReturn([
                'suggestions' => 'To save $1000, reduce dining out by $300, entertainment by $200, and shopping by $500.',
                'target_amount' => $targetAmount,
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/savings-suggestions?target_amount=' . $targetAmount);

        $response->assertOk()
            ->assertJson([
                'target_amount' => $targetAmount,
            ])
            ->assertJsonStructure([
                'suggestions',
                'target_amount',
            ]);
    }

    #[Test]
    public function savings_suggestions_considers_family_obligations(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '8000',
            'age' => '38',
            'is_single' => false,
            'is_family_provider' => true,
            'family_members_count' => 4,
        ]);

        $categories = Category::take(4)->get();
        foreach ($categories as $category) {
            $budget = Budget::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'amount' => 1500,
            ]);

            Stats::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'budget_id' => $budget->id,
                'amount' => 1400,
                'date' => now(),
                'stats_type' => 'monthly',
            ]);
        }

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('getSavingsSuggestions')
            ->once()
            ->andReturn([
                'suggestions' => 'As a family provider for 4 members, focus on optimizing grocery shopping and reducing non-essential subscriptions while maintaining quality of life.',
                'target_amount' => null,
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/savings-suggestions');

        $response->assertOk()
            ->assertJsonStructure([
                'suggestions',
                'target_amount',
            ]);
    }

    #[Test]
    public function savings_suggestions_handles_high_target_amount(): void
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
            'amount' => 750,
            'date' => now(),
            'stats_type' => 'monthly',
        ]);

        $targetAmount = 2000;

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('getSavingsSuggestions')
            ->once()
            ->with($user, $targetAmount)
            ->andReturn([
                'suggestions' => 'Saving $2000 is ambitious given your current salary. Consider increasing income sources or extending the timeline.',
                'target_amount' => $targetAmount,
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/savings-suggestions?target_amount=' . $targetAmount);

        $response->assertOk()
            ->assertJson([
                'target_amount' => $targetAmount,
            ]);
    }

    #[Test]
    public function savings_suggestions_works_for_single_person(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '4000',
            'age' => '26',
            'is_single' => true,
        ]);

        $categories = Category::take(2)->get();
        foreach ($categories as $category) {
            $budget = Budget::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'amount' => 600,
            ]);

            Stats::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'budget_id' => $budget->id,
                'amount' => 550,
                'date' => now(),
                'stats_type' => 'monthly',
            ]);
        }

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('getSavingsSuggestions')
            ->once()
            ->andReturn([
                'suggestions' => 'As a single person, you have flexibility. Consider meal prepping to save on food costs and canceling unused subscriptions.',
                'target_amount' => null,
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/savings-suggestions');

        $response->assertOk()
            ->assertJsonStructure([
                'suggestions',
                'target_amount',
            ]);
    }

    #[Test]
    public function savings_suggestions_handles_minimal_spending(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '3000',
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 400,
        ]);

        Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 380,
            'date' => now(),
            'stats_type' => 'monthly',
        ]);

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('getSavingsSuggestions')
            ->once()
            ->andReturn([
                'suggestions' => 'You are already spending efficiently. Focus on increasing income rather than cutting expenses further.',
                'target_amount' => null,
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/savings-suggestions');

        $response->assertOk()
            ->assertJsonStructure([
                'suggestions',
                'target_amount',
            ]);
    }

    #[Test]
    public function savings_suggestions_with_small_target_amount(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '5500',
        ]);

        $targetAmount = 200;

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('getSavingsSuggestions')
            ->once()
            ->with($user, $targetAmount)
            ->andReturn([
                'suggestions' => 'Saving $200 is achievable by making small adjustments like bringing lunch to work 3 times a week.',
                'target_amount' => $targetAmount,
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/ai/savings-suggestions?target_amount=' . $targetAmount);

        $response->assertOk()
            ->assertJson([
                'target_amount' => $targetAmount,
            ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}