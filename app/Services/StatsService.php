<?php

namespace App\Services;

use App\Models\Stats;
use App\Models\Budget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StatsService
{
    public function getUserStats($user, array $filters, string $sortBy = 'date', string $sortOrder = 'desc', int $perPage = 15)
    {
        $query = Stats::where('user_id', $user->id)
            ->with('category');

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('date', [$filters['start_date'], $filters['end_date']]);
        }

        if (isset($filters['stats_type'])) {
            $query->where('stats_type', $filters['stats_type']);
        }

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    public function getOverviewStats($user, string $period, string $date)
    {
        $dateRange = $this->getDateRange($period, $date);

        $budgets = $user->budgets()->with('category')->get();

        $stats = Stats::where('user_id', $user->id)
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->select('category_id', DB::raw('SUM(amount) as total_spent'))
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        $totalBudget = 0;
        $totalSpent = 0;
        $categoryBreakdown = [];
        $warnings = [];

        foreach ($budgets as $budget) {
            $spent = $stats->get($budget->category_id)?->total_spent ?? 0;
            $remaining = $budget->amount - $spent;
            $percentageUsed = $budget->amount > 0 ? ($spent / $budget->amount) * 100 : 0;

            $totalBudget += $budget->amount;
            $totalSpent += $spent;

            $categoryBreakdown[] = [
                'budget_name' => $budget->name,
                'category_id' => $budget->category_id,
                'category_name' => $budget->category->name,
                'budget' => (float) $budget->amount,
                'spent' => (float) $spent,
                'remaining' => (float) $remaining,
                'percentage_used' => round($percentageUsed, 2),
                'status' => $this->getBudgetStatus($percentageUsed),
            ];

            if ($percentageUsed >= 90) {
                $warnings[] = [
                    'category' => $budget->category->name,
                    'message' => "You've used " . round($percentageUsed, 2) . "% of your budget in {$budget->category->name}",
                    'severity' => $percentageUsed >= 100 ? 'critical' : 'warning',
                ];
            }
        }

        $overallPercentage = $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0;

        return [
            'period' => $period,
            'date_range' => [
                'start' => $dateRange['start']->format('Y-m-d'),
                'end' => $dateRange['end']->format('Y-m-d'),
            ],
            'overview' => [
                'total_budget' => (float) $totalBudget,
                'total_spent' => (float) $totalSpent,
                'total_remaining' => (float) ($totalBudget - $totalSpent),
                'percentage_used' => round($overallPercentage, 2),
                'status' => $this->getBudgetStatus($overallPercentage),
            ],
            'category_breakdown' => $categoryBreakdown,
            'warnings' => $warnings,
            'insights' => $this->generateInsights($categoryBreakdown, $period),
        ];
    }

    public function getCategoryStats($user, $category, string $period, string $date)
    {
        $dateRange = $this->getDateRange($period, $date);

        $budget = $user->budgets()->where('category_id', $category->id)->first();

        if (!$budget) {
            return null;
        }

        $stats = Stats::where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->orderBy('date', 'asc')
            ->get();

        $totalSpent = $stats->sum('amount');
        $remaining = $budget->amount - $totalSpent;
        $percentageUsed = $budget->amount > 0 ? ($totalSpent / $budget->amount) * 100 : 0;

        $dailyBreakdown = $stats->groupBy(function ($stat) {
            return Carbon::parse($stat->date)->format('Y-m-d');
        })->map(function ($dayStats) {
            return [
                'date' => $dayStats->first()->date,
                'amount' => (float) $dayStats->sum('amount'),
                'transaction_count' => $dayStats->count(),
            ];
        })->values();

        $trend = $this->calculateTrend($stats);

        return [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
            'period' => $period,
            'date_range' => [
                'start' => $dateRange['start']->format('Y-m-d'),
                'end' => $dateRange['end']->format('Y-m-d'),
            ],
            'summary' => [
                'budget' => (float) $budget->amount,
                'spent' => (float) $totalSpent,
                'remaining' => (float) $remaining,
                'percentage_used' => round($percentageUsed, 2),
                'status' => $this->getBudgetStatus($percentageUsed),
                'average_daily_spending' => (float) round($stats->avg('amount'), 2),
                'transaction_count' => $stats->count(),
            ],
            'daily_breakdown' => $dailyBreakdown,
            'trend' => $trend,
            'warning' => $percentageUsed >= 90 ? [
                'message' => "You've used " . round($percentageUsed, 2) . "% of your budget",
                'severity' => $percentageUsed >= 100 ? 'critical' : 'warning',
            ] : null,
        ];
    }

    public function createStat($user, $budget, array $data)
    {
        $stat = Stats::create([
            'user_id' => $user->id,
            'budget_id' => $budget->id,
            'category_id' => $budget->category_id,
            'amount' => $data['amount'],
            'date' => $data['date'],
            'time' => $data['time'] ?? now()->format('H:i:s'),
            'stats_type' => $data['stats_type'],
            'description' => $data['description'] ?? null,
        ]);

        $dateRange = $this->getDateRange($data['stats_type'], $data['date']);
        $totalSpent = Stats::where('user_id', $user->id)
            ->where('budget_id', $budget->id)
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->sum('amount');

        $remaining = $budget->amount - $totalSpent;
        $percentageUsed = $budget->amount > 0 ? ($totalSpent / $budget->amount) * 100 : 0;

        $warning = null;
        if ($percentageUsed >= 90) {
            $warning = [
                'message' => $percentageUsed >= 100
                    ? "You have exceeded your budget for this category!"
                    : "Warning: You've used {$percentageUsed}% of your budget for this category.",
                'severity' => $percentageUsed >= 100 ? 'critical' : 'warning',
            ];
        }

        return [
            'success' => true,
            'stat' => $stat->load('category'),
            'budget_status' => [
                'budget' => (float) $budget->amount,
                'spent' => (float) $totalSpent,
                'remaining' => (float) $remaining,
                'percentage_used' => round($percentageUsed, 2),
                'status' => $this->getBudgetStatus($percentageUsed),
            ],
            'warning' => $warning,
        ];
    }

    public function updateStat(Stats $stat, array $data)
    {
        $stat->update($data);
        return $stat->fresh()->load('category');
    }

    public function deleteStat(Stats $stat)
    {
        $stat->delete();
    }

    public function getDateRange(string $period, string $date): array
    {
        $baseDate = Carbon::parse($date);

        return match ($period) {
            'daily' => [
                'start' => $baseDate->copy()->startOfDay(),
                'end' => $baseDate->copy()->endOfDay(),
            ],
            'weekly' => [
                'start' => $baseDate->copy()->startOfWeek(),
                'end' => $baseDate->copy()->endOfWeek(),
            ],
            'monthly' => [
                'start' => $baseDate->copy()->startOfMonth(),
                'end' => $baseDate->copy()->endOfMonth(),
            ],
            'quarterly' => [
                'start' => $baseDate->copy()->startOfQuarter(),
                'end' => $baseDate->copy()->endOfQuarter(),
            ],
            'yearly' => [
                'start' => $baseDate->copy()->startOfYear(),
                'end' => $baseDate->copy()->endOfYear(),
            ],
            default => [
                'start' => $baseDate->copy()->startOfMonth(),
                'end' => $baseDate->copy()->endOfMonth(),
            ],
        };
    }

    public function getBudgetStatus(float $percentage): string
    {
        if ($percentage >= 100) {
            return 'exceeded';
        } elseif ($percentage >= 90) {
            return 'critical';
        } elseif ($percentage >= 75) {
            return 'warning';
        } elseif ($percentage >= 50) {
            return 'moderate';
        } else {
            return 'good';
        }
    }

    public function generateInsights(array $categoryBreakdown, string $period): array
    {
        $insights = [];

        $highestSpending = collect($categoryBreakdown)->sortByDesc('spent')->first();
        if ($highestSpending && $highestSpending['spent'] > 0) {
            $insights[] = [
                'type' => 'highest_spending',
                'message' => "Your highest spending category is {$highestSpending['category_name']} with {$highestSpending['spent']} spent",
            ];
        }

        $goodCategories = collect($categoryBreakdown)
            ->filter(fn($cat) => $cat['percentage_used'] > 0 && $cat['percentage_used'] < 75)
            ->count();

        if ($goodCategories > 0) {
            $insights[] = [
                'type' => 'positive',
                'message' => "You're managing {$goodCategories} categories well, staying under 75% of budget",
            ];
        }

        $atRisk = collect($categoryBreakdown)
            ->filter(fn($cat) => $cat['percentage_used'] >= 90 && $cat['percentage_used'] < 100)
            ->count();

        if ($atRisk > 0) {
            $insights[] = [
                'type' => 'warning',
                'message' => "{$atRisk} categories are at risk of exceeding budget",
            ];
        }

        return $insights;
    }

    public function calculateTrend($stats): string
    {
        if ($stats->count() < 2) {
            return 'insufficient_data';
        }

        $firstHalf = $stats->take($stats->count() / 2)->sum('amount');
        $secondHalf = $stats->skip($stats->count() / 2)->sum('amount');

        if ($secondHalf > $firstHalf * 1.1) {
            return 'increasing';
        } elseif ($secondHalf < $firstHalf * 0.9) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }
}
