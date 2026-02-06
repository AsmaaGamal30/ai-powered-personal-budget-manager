<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Services\AIAssistantService;
use Illuminate\Http\Request;

class AIAssistantController extends Controller
{
    protected AIAssistantService $aiAssistantService;

    public function __construct(AIAssistantService $aiAssistantService)
    {
        $this->aiAssistantService = $aiAssistantService;
    }

    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'context' => 'nullable|array',
        ]);

        $user = auth()->user();
        $message = $request->input('message');
        $additionalContext = $request->input('context', []);

        $response = $this->aiAssistantService->chat($message, $user, $additionalContext);

        return response()->json($response);
    }

    public function getInsights(Request $request)
    {
        $user = auth()->user();
        $period = $request->input('period', 'monthly');
        $date = $request->input('date', now()->format('Y-m-d'));

        $insights = $this->aiAssistantService->getInsights($user, $period, $date);

        return response()->json($insights);
    }

    public function getBudgetRecommendations(Request $request)
    {
        $user = auth()->user();
        $categoryId = $request->input('category_id');

        $recommendations = $this->aiAssistantService->getBudgetRecommendations($user, $categoryId);

        return response()->json($recommendations);
    }

    public function analyzeAnomalies(Request $request)
    {
        $user = auth()->user();
        $period = $request->input('period', 'monthly');

        $anomalies = $this->aiAssistantService->analyzeAnomalies($user, $period);

        return response()->json($anomalies);
    }

    public function getSavingsSuggestions(Request $request)
    {
        $user = auth()->user();
        $targetAmount = $request->input('target_amount');

        $suggestions = $this->aiAssistantService->getSavingsSuggestions($user, $targetAmount);

        return response()->json($suggestions);
    }
}
