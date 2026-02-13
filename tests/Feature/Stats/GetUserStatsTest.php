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

class GetUserStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    #[Test]
    public function get_user_stats_requires_authentication(): void
    {
        $response = $this->getJson('/api/stats/records');

        $response->assertUnauthorized();
    }

    #[Test]
    public function get_user_stats_returns_paginated_results(): void
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

        for ($i = 0; $i < 20; $i++) {
            Stats::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'budget_id' => $budget->id,
                'amount' => 50 + $i,
                'date' => now()->subDays($i),
                'stats_type' => 'daily',
            ]);
        }

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/records');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'category_id',
                        'category',
                        'budget_id',
                        'amount',
                        'date',
                        'stats_type',
                    ]
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(15, 'data');
    }

    #[Test]
    public function get_user_stats_filters_by_category(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
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
                'amount' => 100,
                'date' => now(),
                'stats_type' => 'daily',
            ]);
        }

        $filterCategory = $categories->first();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/records?category_id=' . $filterCategory->id);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category_id', $filterCategory->id);
    }

    #[Test]
    public function get_user_stats_filters_by_date_range(): void
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

        Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 50,
            'date' => now()->subDays(30),
            'stats_type' => 'daily',
        ]);

        Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 100,
            'date' => now()->subDays(5),
            'stats_type' => 'daily',
        ]);

        $startDate = now()->subDays(7)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/stats/records?start_date={$startDate}&end_date={$endDate}");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function get_user_stats_filters_by_stats_type(): void
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

        Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 100,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 500,
            'date' => now(),
            'stats_type' => 'monthly',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/records?stats_type=daily');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.stats_type', 'daily');
    }

    #[Test]
    public function get_user_stats_supports_custom_sorting(): void
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

        Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 50,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        Stats::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'budget_id' => $budget->id,
            'amount' => 150,
            'date' => now()->subDays(1),
            'stats_type' => 'daily',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/records?sort_by=amount&sort_order=asc');

        $response->assertOk()
            ->assertJsonPath('data.0.amount', 50);
    }

    #[Test]
    public function get_user_stats_supports_custom_per_page(): void
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

        for ($i = 0; $i < 10; $i++) {
            Stats::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'budget_id' => $budget->id,
                'amount' => 50,
                'date' => now()->subDays($i),
                'stats_type' => 'daily',
            ]);
        }

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/records?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    #[Test]
    public function get_user_stats_only_returns_current_user_stats(): void
    {
        $user1 = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user2 = User::factory()->create([
            'email_verified_at' => now(),
        ]);

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

        Stats::create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'budget_id' => $budget1->id,
            'amount' => 100,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        Stats::create([
            'user_id' => $user2->id,
            'category_id' => $category->id,
            'budget_id' => $budget2->id,
            'amount' => 200,
            'date' => now(),
            'stats_type' => 'daily',
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->getJson('/api/stats/records');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_id', $user1->id);
    }
}