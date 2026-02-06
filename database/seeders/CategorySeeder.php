<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $categories = [
            [
                'name' => 'Housing',
                'description' => 'Rent, utilities, internet, and home maintenance expenses.',
            ],
            [
                'name' => 'Food & Dining',
                'description' => 'Groceries, restaurants, cafes, and food delivery.',
            ],
            [
                'name' => 'Transportation',
                'description' => 'Public transport, fuel, ride-hailing, and vehicle maintenance.',
            ],
            [
                'name' => 'Health & Medical',
                'description' => 'Doctor visits, medications, insurance, and wellness expenses.',
            ],
            [
                'name' => 'Personal & Lifestyle',
                'description' => 'Clothing, personal care, gym, and hobbies.',
            ],
            [
                'name' => 'Education',
                'description' => 'Courses, books, certifications, and learning subscriptions.',
            ],
            [
                'name' => 'Entertainment',
                'description' => 'Movies, streaming services, games, and events.',
            ],
            [
                'name' => 'Shopping',
                'description' => 'Electronics, home items, accessories, and online purchases.',
            ],
            [
                'name' => 'Bills & Subscriptions',
                'description' => 'Phone plans, streaming platforms, and software subscriptions.',
            ],
            [
                'name' => 'Savings',
                'description' => 'Emergency fund, investments, and financial goals.',
            ],
            [
                'name' => 'Donations & Gifts',
                'description' => 'Charity donations, gifts for others, and special occasions.',
            ],
            [
                'name' => 'Other',
                'description' => 'Other expenses that do not fit into the above categories.',
            ]
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}