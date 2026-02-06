<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetUserStatsRequest;
use App\Http\Requests\StoreStatsRequest;
use App\Http\Requests\UpdateStatsRequest;
use App\Http\Resources\CategoryStatsResource;
use App\Http\Resources\StatsOverviewResource;
use App\Http\Resources\StatsResource;
use App\Models\Category;
use App\Models\Stats;
use App\Services\StatsService;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    protected $statsService;

    public function __construct(StatsService $statsService)
    {
        $this->statsService = $statsService;
    }

    public function getUserStats(GetUserStatsRequest $request)
    {
        $user = auth()->user();

        $filters = $request->only(['category_id', 'start_date', 'end_date', 'stats_type']);
        $sortBy = $request->input('sort_by', 'date');
        $sortOrder = $request->input('sort_order', 'desc');
        $perPage = $request->input('per_page', 15);

        $stats = $this->statsService->getUserStats($user, $filters, $sortBy, $sortOrder, $perPage);

        return StatsResource::collection($stats);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $period = $request->input('period', 'monthly');
        $date = $request->input('date', now()->format('Y-m-d'));

        $data = $this->statsService->getOverviewStats($user, $period, $date);

        return new StatsOverviewResource($data);
    }

    public function show(Request $request, Category $category)
    {
        $user = auth()->user();
        $period = $request->input('period', 'monthly');
        $date = $request->input('date', now()->format('Y-m-d'));

        $data = $this->statsService->getCategoryStats($user, $category, $period, $date);

        if (!$data) {
            return response()->json([
                'message' => 'No budget found for this category'
            ], 404);
        }

        return new CategoryStatsResource($data);
    }

    public function store(StoreStatsRequest $request)
    {
        $user = auth()->user();

        $result = $this->statsService->createStat($user, $request->validated());

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 422);
        }

        return (new StatsResource($result['stat']))
            ->additional([
                'message' => 'Spending recorded successfully',
                'budget_status' => $result['budget_status'],
                'warning' => $result['warning'],
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateStatsRequest $request, Stats $stat)
    {
        $user = auth()->user();

        if ($stat->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $updatedStat = $this->statsService->updateStat($stat, $request->validated());

        return (new StatsResource($updatedStat))
            ->additional([
                'message' => 'Spending record updated successfully',
            ]);
    }

    public function destroy(Stats $stat)
    {
        $user = auth()->user();

        if ($stat->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->statsService->deleteStat($stat);

        return response()->json([
            'message' => 'Spending record deleted successfully',
        ]);
    }
}
