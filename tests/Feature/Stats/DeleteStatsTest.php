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

class DeleteStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    #[Test]
    public function delete_stats_requires_authentication(): void
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

        $response = $this->deleteJson('/api/stats/' . $stat->id);

        $response->assertUnauthorized();
    }

    #[Test]
    public function delete_stats_requires_ownership(): void
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
            ->deleteJson('/api/stats/' . $stat->id);

        $response->assertForbidden();
    }

    #[Test]
    public function delete_stats_deletes_successfully(): void
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
            ->deleteJson('/api/stats/' . $stat->id);

        $response->assertOk()
            ->assertJson([
                'message' => 'Spending record deleted successfully',
            ]);

        $this->assertDatabaseMissing('stats', [
            'id' => $stat->id,
        ]);
    }

    #[Test]
    public function delete_stats_returns_404_for_non_existent_stat(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/stats/99999');

        $response->assertNotFound();
    }

    #[Test]
    public function delete_stats_soft_deletes_if_configured(): void
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

        $statId = $stat->id;

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/stats/' . $statId);

        $response->assertOk();

        $this->assertDatabaseMissing('stats', [
            'id' => $statId,
        ]);
    }

    #[Test]
    public function delete_stats_allows_owner_to_delete_any_of_their_stats(): void
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

        $stat1 = Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 100,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        $stat2 = Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 200,
            'date' => now()->subDays(1),
            'stats_type' => 'daily',
        ]);

        $response1 = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/stats/' . $stat1->id);

        $response2 = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/stats/' . $stat2->id);

        $response1->assertOk();
        $response2->assertOk();

        $this->assertDatabaseMissing('stats', ['id' => $stat1->id]);
        $this->assertDatabaseMissing('stats', ['id' => $stat2->id]);
    }

    #[Test]
    public function delete_stats_does_not_affect_other_users_stats(): void
    {
        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);

        $category = Category::first();

        $budget1 = Budget::create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $budget2 = Budget::create([
            'user_id' => $user2->id,
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $stat1 = Stats::create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'budget_id' => $budget1->id,
            'amount' => 100,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        $stat2 = Stats::create([
            'user_id' => $user2->id,
            'category_id' => $category->id,
            'budget_id' => $budget2->id,
            'amount' => 200,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->deleteJson('/api/stats/' . $stat1->id);

        $response->assertOk();

        $this->assertDatabaseMissing('stats', ['id' => $stat1->id]);
        $this->assertDatabaseHas('stats', ['id' => $stat2->id]);
    }

    #[Test]
    public function delete_stats_returns_success_message(): void
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
            ->deleteJson('/api/stats/' . $stat->id);

        $response->assertOk()
            ->assertJsonStructure(['message'])
            ->assertJson([
                'message' => 'Spending record deleted successfully',
            ]);
    }
}