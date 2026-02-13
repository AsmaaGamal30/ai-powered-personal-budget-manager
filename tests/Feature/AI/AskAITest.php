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

class AskAITest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    #[Test]
    public function ask_endpoint_requires_authentication(): void
    {
        $response = $this->postJson('/api/ai/ask', [
            'message' => 'How am I doing with my budget?',
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function ask_endpoint_requires_message(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/ai/ask', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['message']);
    }

    #[Test]
    public function ask_endpoint_validates_message_max_length(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/ai/ask', [
                'message' => str_repeat('a', 1001),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['message']);
    }
    #[Test]

    public function ask_endpoint_accepts_optional_context(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '5000',
            'age' => '30',
        ]);

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('chat')
            ->once()
            ->with(
                'What should I do with my money?',
                $user,
                ['period' => 'monthly']
            )
            ->andReturn([
                'message' => 'What should I do with my money?',
                'response' => 'Based on your financial data, here are some suggestions...',
                'usage' => ['total_tokens' => 100],
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/ai/ask', [
                'message' => 'What should I do with my money?',
                'context' => ['period' => 'monthly'],
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'response',
                'usage',
            ]);
    }

    #[Test]
    public function ask_endpoint_returns_ai_response(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'salary' => '5000',
            'age' => '30',
            'gender' => 'male',
            'is_single' => false,
            'is_family_provider' => true,
            'family_members_count' => 3,
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        Stats::create([
            'budget_id' => $budget->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 500,
            'stats_type' => 'monthly',
            'date' => now(),
        ]);

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('chat')
            ->once()
            ->andReturn([
                'message' => 'How is my spending this month?',
                'response' => 'Your spending is on track. You have used 50% of your budget.',
                'usage' => ['total_tokens' => 150],
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/ai/ask', [
                'message' => 'How is my spending this month?',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'How is my spending this month?',
                'response' => 'Your spending is on track. You have used 50% of your budget.',
            ]);
    }

    #[Test]
    public function ask_endpoint_works_with_minimal_user_data(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $aiService = Mockery::mock(AIAssistantService::class);
        $aiService->shouldReceive('chat')
            ->once()
            ->andReturn([
                'message' => 'Give me some financial advice',
                'response' => 'Start by tracking your expenses and setting a budget.',
                'usage' => ['total_tokens' => 120],
            ]);

        $this->app->instance(AIAssistantService::class, $aiService);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/ai/ask', [
                'message' => 'Give me some financial advice',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'response',
            ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}