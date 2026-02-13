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

class UpdateStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    #[Test]
    public function update_stats_requires_authentication(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $stat = Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 100,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        $response = $this->putJson('/api/stats/' . $stat->id, [
            'amount' => 150,
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function update_stats_requires_ownership(): void
    {
        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);

        $category = Category::first();
        $budget = Budget::create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $stat = Stats::create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 100,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        $response = $this->actingAs($user2, 'sanctum')
            ->putJson('/api/stats/' . $stat->id, [
                'amount' => 150,
            ]);

        $response->assertForbidden();
    }

    #[Test]
    public function update_stats_updates_amount_successfully(): void
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

        $stat = Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 100,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        $newAmount = 150;

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/stats/' . $stat->id, [
                'amount' => $newAmount,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.amount', $newAmount)
            ->assertJsonPath('message', 'Spending record updated successfully');

        $this->assertDatabaseHas('stats', [
            'id' => $stat->id,
            'amount' => $newAmount,
        ]);
    }

    #[Test]
    public function update_stats_updates_date_successfully(): void
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

        $stat = Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 100,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        $newDate = now()->subDays(2)->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/stats/' . $stat->id, [
                'date' => $newDate,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.date', $newDate);

        $this->assertDatabaseHas('stats', [
            'id' => $stat->id,
            'date' => $newDate,
        ]);
    }

    #[Test]
    public function update_stats_updates_stats_type_successfully(): void
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

        $stat = Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 100,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/stats/' . $stat->id, [
                'stats_type' => 'weekly',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.stats_type', 'weekly');

        $this->assertDatabaseHas('stats', [
            'id' => $stat->id,
            'stats_type' => 'weekly',
        ]);
    }

    #[Test]
    public function update_stats_updates_time_successfully(): void
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

        $stat = Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 100,
            'date' => now(),
            'time' => '10:00:00',
            'stats_type' => 'daily',
        ]);

        $newTime = '15:30:00';

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/stats/' . $stat->id, [
                'time' => $newTime,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.time', $newTime);
    }

    #[Test]
    public function update_stats_updates_description_successfully(): void
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

        $stat = Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 100,
            'date' => now(),
            'stats_type' => 'daily',
            'description' => 'Old description',
        ]);

        $newDescription = 'Updated grocery shopping';

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/stats/' . $stat->id, [
                'description' => $newDescription,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.description', $newDescription);

        $this->assertDatabaseHas('stats', [
            'id' => $stat->id,
            'description' => $newDescription,
        ]);
    }

    #[Test]
    public function update_stats_updates_multiple_fields_at_once(): void
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

        $stat = Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 100,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/stats/' . $stat->id, [
                'amount' => 200,
                'date' => now()->subDays(1)->format('Y-m-d'),
                'stats_type' => 'weekly',
                'description' => 'Updated record',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.amount', 200)
            ->assertJsonPath('data.stats_type', 'weekly')
            ->assertJsonPath('data.description', 'Updated record');
    }

    #[Test]
    public function update_stats_validates_amount_minimum(): void
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

        $stat = Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 100,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/stats/' . $stat->id, [
                'amount' => 0,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function update_stats_validates_stats_type(): void
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

        $stat = Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 100,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/stats/' . $stat->id, [
                'stats_type' => 'invalid_type',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['stats_type']);
    }

    #[Test]
    public function update_stats_validates_description_max_length(): void
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

        $stat = Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 100,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/stats/' . $stat->id, [
                'description' => str_repeat('a', 501),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    }

    #[Test]
    public function update_stats_returns_updated_resource(): void
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

        $stat = Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 100,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/stats/' . $stat->id, [
                'amount' => 125,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'category_id',
                    'category',
                    'budget_id',
                    'amount',
                    'date',
                    'stats_type',
                ],
                'message',
            ]);
    }
}
