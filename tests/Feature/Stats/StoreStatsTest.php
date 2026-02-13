<?php

namespace Tests\Feature\Stats;

use App\Models\User;
use App\Models\Category;
use App\Models\Budget;
use App\Models\Stats;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class StoreStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    #[Test]
    public function store_stats_requires_authentication(): void
    {
        $category = Category::first();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $response = $this->postJson('/api/stats/' . $budget->id, [
            'amount' => 100,
            'date' => now()->format('Y-m-d'),
            'stats_type' => 'daily',
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function store_stats_requires_amount(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/stats/' . $budget->id, [
                'date' => now()->format('Y-m-d'),
                'stats_type' => 'daily',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function store_stats_requires_date(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/stats/' . $budget->id, [
                'amount' => 100,
                'stats_type' => 'daily',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['date']);
    }

    #[Test]
    public function store_stats_requires_stats_type(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/stats/' . $budget->id, [
                'amount' => 100,
                'date' => now()->format('Y-m-d'),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['stats_type']);
    }

    #[Test]
    public function store_stats_validates_stats_type_values(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/stats/' . $budget->id, [
                'amount' => 100,
                'date' => now()->format('Y-m-d'),
                'stats_type' => 'invalid',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['stats_type']);
    }

    #[Test]
    public function store_stats_validates_minimum_amount(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/stats/' . $budget->id, [
                'amount' => 0,
                'date' => now()->format('Y-m-d'),
                'stats_type' => 'daily',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function store_stats_creates_stat_successfully(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/stats/' . $budget->id, [
                'amount' => 150,
                'date' => now()->format('Y-m-d'),
                'stats_type' => 'daily',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'category_id',
                    'budget_id',
                    'amount',
                    'date',
                    'stats_type',
                ],
                'message',
                'budget_status',
                'warning',
            ])
            ->assertJsonPath('data.amount', 150)
            ->assertJsonPath('data.stats_type', 'daily');

        $this->assertDatabaseHas('stats', [
            'user_id' => $user->id,
            'budget_id' => $budget->id,
            'amount' => 150,
            'stats_type' => 'daily',
        ]);
    }

    #[Test]
    public function store_stats_accepts_optional_time(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/stats/' . $budget->id, [
                'amount' => 100,
                'date' => now()->format('Y-m-d'),
                'time' => '14:30:00',
                'stats_type' => 'daily',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.time', '14:30:00');
    }

    #[Test]
    public function store_stats_accepts_optional_description(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $description = 'Grocery shopping';

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/stats/' . $budget->id, [
                'amount' => 100,
                'date' => now()->format('Y-m-d'),
                'stats_type' => 'daily',
                'description' => $description,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.description', $description);
    }

    #[Test]
    public function store_stats_returns_warning_when_approaching_limit(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/stats/' . $budget->id, [
                'amount' => 950,
                'date' => now()->format('Y-m-d'),
                'stats_type' => 'daily',
            ]);

        $response->assertCreated()
            ->assertJsonPath('budget_status.status', 'critical');
    }

    #[Test]
    public function store_stats_supports_all_stats_types(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 5000,
        ]);

        $statsTypes = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'];

        foreach ($statsTypes as $type) {
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/stats/' . $budget->id, [
                    'amount' => 100,
                    'date' => now()->format('Y-m-d'),
                    'stats_type' => $type,
                ]);

            $response->assertCreated()
                ->assertJsonPath('data.stats_type', $type);
        }
    }

    #[Test]
    public function store_stats_validates_description_max_length(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/stats/' . $budget->id, [
                'amount' => 100,
                'date' => now()->format('Y-m-d'),
                'stats_type' => 'daily',
                'description' => str_repeat('a', 501),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    }
}
