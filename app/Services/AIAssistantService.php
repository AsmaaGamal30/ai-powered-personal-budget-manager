<?php

namespace App\Services;

use App\Models\Stats;
use Carbon\Carbon;

class AIAssistantService
{
    protected LLMService $LLMService;

    public function __construct(LLMService $LLMService)
    {
        $this->LLMService = $LLMService;
    }

    public function chat(string $message, $user, array $additionalContext = []): array
    {
        $financialContext = $this->gatherFinancialContext($user, $additionalContext);
        $response = $this->LLMService->chat($message, $financialContext);

        return [
            'message' => $message,
            'response' => $response['content'],
            'usage' => $response['usage'] ?? null,
        ];
    }

    public function getInsights($user, string $period = 'monthly', ?string $date = null): array
    {
        $date = $date ?? now()->format('Y-m-d');

        $financialContext = $this->gatherFinancialContext($user, [
            'period' => $period,
            'date' => $date,
            'detailed' => true,
        ]);

        $prompt = "Based on the user's profile and spending data, provide detailed financial insights including:
1. Spending patterns analysis (considering their life stage and responsibilities)
2. Budget optimization recommendations tailored to their situation
3. Areas where they can save money
4. Positive spending habits to maintain
5. Warning about any concerning trends
6. Age-appropriate financial advice and planning suggestions

be specific, actionable, and personalized to their demographic profile.";

        $response = $this->LLMService->chat($prompt, $financialContext);

        return [
            'period' => $period,
            'insights' => $response['content'],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function getBudgetRecommendations($user, ?int $categoryId = null): array
    {
        $financialContext = $this->gatherFinancialContext($user, [
            'category_id' => $categoryId,
            'include_history' => true,
        ]);

        $prompt = $categoryId
            ? "Analyze the spending in this specific category and recommend an optimal budget amount. Consider the user's salary, family situation, and historical spending patterns. Provide justification."
            : "Review all spending categories and recommend optimal budget allocations based on the user's income, family responsibilities, and life stage. Provide specific amounts and reasoning for each category.";

        $response = $this->LLMService->chat($prompt, $financialContext);

        return [
            'recommendations' => $response['content'],
        ];
    }

    public function analyzeAnomalies($user, string $period = 'monthly'): array
    {
        $financialContext = $this->gatherFinancialContext($user, [
            'period' => $period,
            'include_daily_breakdown' => true,
        ]);

        $prompt = "Analyze the spending data for unusual patterns or anomalies, considering the user's typical financial situation and responsibilities. Identify:
1. Any sudden spikes in spending
2. Categories with irregular patterns
3. Potential budget risks (especially important given their family situation)
4. Unusual transactions that need attention
Be specific about dates and amounts.";

        $response = $this->LLMService->chat($prompt, $financialContext);

        return [
            'anomalies' => $response['content'],
        ];
    }

    public function getSavingsSuggestions($user, ?float $targetAmount = null): array
    {
        $financialContext = $this->gatherFinancialContext($user, [
            'target_savings' => $targetAmount,
        ]);

        $prompt = $targetAmount
            ? "The user wants to save {$targetAmount}. Analyze their spending considering their income, family obligations, and life situation. Provide specific, realistic, actionable suggestions on how to achieve this savings goal. Include which categories to reduce and by how much."
            : "Analyze the user's spending and identify opportunities to save money. Consider their salary, family responsibilities, age, and life stage. Provide specific, actionable suggestions for each category that are realistic for their situation.";

        $response = $this->LLMService->chat($prompt, $financialContext);

        return [
            'suggestions' => $response['content'],
            'target_amount' => $targetAmount,
        ];
    }

    public function gatherFinancialContext($user, array $options = []): array
    {
        $period = $options['period'] ?? 'monthly';
        $date = $options['date'] ?? now()->format('Y-m-d');
        $categoryId = $options['category_id'] ?? null;

        $dateRange = $this->getDateRange($period, $date);

        $budgetsQuery = $user->budgets()->with('category');
        if ($categoryId) {
            $budgetsQuery->where('category_id', $categoryId);
        }
        $budgets = $budgetsQuery->get();

        $statsQuery = Stats::where('user_id', $user->id)
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']]);

        if ($categoryId) {
            $statsQuery->where('category_id', $categoryId);
        }

        $stats = $statsQuery->with('category')->get();

        $summary = [];
        foreach ($budgets as $budget) {
            $categoryStats = $stats->where('category_id', $budget->category_id);
            $spent = $categoryStats->sum('amount');
            $remaining = $budget->amount - $spent;
            $percentageUsed = $budget->amount > 0 ? ($spent / $budget->amount) * 100 : 0;

            $summary[] = [
                'category' => $budget->category->name,
                'budget' => (float) $budget->amount,
                'spent' => (float) $spent,
                'remaining' => (float) $remaining,
                'percentage_used' => round($percentageUsed, 2),
                'transaction_count' => $categoryStats->count(),
                'average_transaction' => $categoryStats->count() > 0 ? round($spent / $categoryStats->count(), 2) : 0,
            ];
        }

        $context = [
            'user_profile' => $this->getUserProfileContext($user),
            'analysis_period' => $period,
            'date_range' => [
                'start' => $dateRange['start']->format('Y-m-d'),
                'end' => $dateRange['end']->format('Y-m-d'),
            ],
            'total_budget' => (float) $budgets->sum('amount'),
            'total_spent' => (float) $stats->sum('amount'),
            'categories_summary' => $summary,
        ];

        if ($user->salary) {
            $monthlySalary = (float) $user->salary;
            $totalSpent = (float) $stats->sum('amount');

            $periodMonths = match ($period) {
                'daily' => 1 / 30,
                'weekly' => 1 / 4,
                'monthly' => 1,
                'quarterly' => 3,
                'yearly' => 12,
                default => 1,
            };

            $periodIncome = $monthlySalary * $periodMonths;
            $spendingRatio = $periodIncome > 0 ? round(($totalSpent / $periodIncome) * 100, 2) : 0;

            $context['financial_metrics'] = [
                'period_income' => $periodIncome,
                'spending_to_income_ratio' => $spendingRatio,
                'remaining_income' => $periodIncome - $totalSpent,
            ];
        }

        if ($options['include_daily_breakdown'] ?? false) {
            $context['daily_breakdown'] = $stats->groupBy(function ($stat) {
                return Carbon::parse($stat->date)->format('Y-m-d');
            })->map(function ($dayStats) {
                return [
                    'date' => $dayStats->first()->date,
                    'amount' => (float) $dayStats->sum('amount'),
                    'transactions' => $dayStats->count(),
                ];
            })->values();
        }

        if ($options['include_history'] ?? false) {
            $lastPeriodRange = $this->getPreviousPeriodRange($period, $date);
            $historicalStats = Stats::where('user_id', $user->id)
                ->whereBetween('date', [$lastPeriodRange['start'], $lastPeriodRange['end']])
                ->get();

            $context['previous_period'] = [
                'date_range' => [
                    'start' => $lastPeriodRange['start']->format('Y-m-d'),
                    'end' => $lastPeriodRange['end']->format('Y-m-d'),
                ],
                'total_spent' => (float) $historicalStats->sum('amount'),
            ];
        }

        if (isset($options['target_savings'])) {
            $context['target_savings'] = $options['target_savings'];
        }

        return $context;
    }

    protected function getUserProfileContext($user): array
    {
        $profile = [
            'user_id' => $user->id,
        ];

        if ($user->salary) {
            $profile['monthly_salary'] = (float) $user->salary;
        }

        if ($user->age) {
            $profile['age'] = (int) $user->age;
            $profile['life_stage'] = $this->determineLifeStage((int) $user->age);
        }

        if ($user->gender) {
            $profile['gender'] = $user->gender;
        }

        $profile['relationship_status'] = $user->is_single ? 'single' : 'in_relationship';

        $profile['is_family_provider'] = (bool) $user->is_family_provider;

        if ($user->is_family_provider && $user->family_members_count) {
            $profile['family_members_count'] = (int) $user->family_members_count;
        }

        $profile['financial_responsibility_level'] = $this->determineResponsibilityLevel($user);

        return $profile;
    }

    protected function determineLifeStage(int $age): string
    {
        return match (true) {
            $age < 25 => 'young_adult',
            $age < 35 => 'early_career',
            $age < 45 => 'mid_career',
            $age < 55 => 'established_career',
            $age < 65 => 'pre_retirement',
            default => 'retirement_age',
        };
    }

    protected function determineResponsibilityLevel($user): string
    {
        if ($user->is_family_provider && $user->family_members_count > 2) {
            return 'high';
        } elseif ($user->is_family_provider) {
            return 'moderate_to_high';
        } elseif (!$user->is_single) {
            return 'moderate';
        } else {
            return 'individual';
        }
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

    public function getPreviousPeriodRange(string $period, string $date): array
    {
        $baseDate = Carbon::parse($date);

        return match ($period) {
            'daily' => [
                'start' => $baseDate->copy()->subDay()->startOfDay(),
                'end' => $baseDate->copy()->subDay()->endOfDay(),
            ],
            'weekly' => [
                'start' => $baseDate->copy()->subWeek()->startOfWeek(),
                'end' => $baseDate->copy()->subWeek()->endOfWeek(),
            ],
            'monthly' => [
                'start' => $baseDate->copy()->subMonth()->startOfMonth(),
                'end' => $baseDate->copy()->subMonth()->endOfMonth(),
            ],
            'quarterly' => [
                'start' => $baseDate->copy()->subQuarter()->startOfQuarter(),
                'end' => $baseDate->copy()->subQuarter()->endOfQuarter(),
            ],
            'yearly' => [
                'start' => $baseDate->copy()->subYear()->startOfYear(),
                'end' => $baseDate->copy()->subYear()->endOfYear(),
            ],
            default => [
                'start' => $baseDate->copy()->subMonth()->startOfMonth(),
                'end' => $baseDate->copy()->subMonth()->endOfMonth(),
            ],
        };
    }
}
