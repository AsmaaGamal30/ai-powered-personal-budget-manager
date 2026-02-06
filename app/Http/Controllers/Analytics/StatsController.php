<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Stats;
use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsController extends Controller
{
    public function getUserStats(Request $request)
    {
        $user = auth()->user();

        $query = Stats::where('user_id', $user->id)
            ->with('category');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        if ($request->has('stats_type')) {
            $query->where('stats_type', $request->stats_type);
        }

        $sortBy = $request->input('sort_by', 'date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 15);
        $stats = $query->paginate($perPage);

        return response()->json($stats);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $period = $request->input('period', 'monthly');
        $date = $request->input('date', now()->format('Y-m-d'));

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

        return response()->json([
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
        ]);
    }

    public function show(Request $request, Category $category)
    {
        $user = auth()->user();
        $period = $request->input('period', 'monthly');
        $date = $request->input('date', now()->format('Y-m-d'));

        $dateRange = $this->getDateRange($period, $date);

        $budget = $user->budgets()->where('category_id', $category->id)->first();

        if (!$budget) {
            return response()->json([
                'message' => 'No budget found for this category'
            ], 404);
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

        return response()->json([
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
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i:s',
            'stats_type' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'description' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        $budget = $user->budgets()->where('category_id', $validated['category_id'])->first();

        if (!$budget) {
            return response()->json([
                'message' => 'You need to create a budget for this category first.'
            ], 422);
        }

        $stat = Stats::create([
            'user_id' => $user->id,
            'category_id' => $validated['category_id'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'time' => $validated['time'] ?? now()->format('H:i:s'),
            'stats_type' => $validated['stats_type'],
            'description' => $validated['description'] ?? null,
        ]);

        $dateRange = $this->getDateRangeForStatType($validated['stats_type'], $validated['date']);
        $totalSpent = Stats::where('user_id', $user->id)
            ->where('category_id', $validated['category_id'])
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

        return response()->json([
            'message' => 'Spending recorded successfully',
            'stat' => $stat->load('category'),
            'budget_status' => [
                'budget' => (float) $budget->amount,
                'spent' => (float) $totalSpent,
                'remaining' => (float) $remaining,
                'percentage_used' => round($percentageUsed, 2),
                'status' => $this->getBudgetStatus($percentageUsed),
            ],
            'warning' => $warning,
        ], 201);
    }

    public function update(Request $request, Stats $stat)
    {
        $user = auth()->user();

        if ($stat->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'amount' => 'sometimes|numeric|min:0.01',
            'date' => 'sometimes|date',
            'time' => 'nullable|date_format:H:i:s',
            'stats_type' => 'sometimes|in:daily,weekly,monthly,quarterly,yearly',
            'description' => 'nullable|string|max:500',
        ]);

        $stat->update($validated);

        return response()->json([
            'message' => 'Spending record updated successfully',
            'stat' => $stat->fresh()->load('category'),
        ]);
    }

    public function destroy(Stats $stat)
    {
        $user = auth()->user();

        if ($stat->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $stat->delete();

        return response()->json([
            'message' => 'Spending record deleted successfully',
        ]);
    }

    private function getDateRangeForStatType(string $statsType, string $date): array
    {
        return $this->getDateRange($statsType, $date);
    }

    private function getDateRange(string $period, string $date): array
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

    private function getBudgetStatus(float $percentage): string
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


    private function generateInsights(array $categoryBreakdown, string $period): array
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


    private function calculateTrend($stats): string
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
