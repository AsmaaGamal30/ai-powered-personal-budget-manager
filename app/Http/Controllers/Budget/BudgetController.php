<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Support\Facades\Request;

class BudgetController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $budgets = $user->budgets()->with(['category'])->get();
        return response()->json($budgets);
    }

    public function show(Category $category)
    {
        $user = auth()->user();
        $budget = $user->budgets()->where('category_id', $category->id)->with(['category', 'stats'])->firstOrFail();
        return response()->json($budget);
    }

    public function store(Category $category, Request $request)
    {
        $user = auth()->user();
        $budget = $user->budgets()->create([
            'category_id' => $category->id,
            'amount' => $request->input('amount'),
        ]);

        return response()->json($budget, 201);
    }

    public function update(Category $category, Request $request)
    {
        $user = auth()->user();
        $budget = $user->budgets()->where('category_id', $category->id)->firstOrFail();
        $budget->update([
            'amount' => $request->input('amount'),
        ]);

        return response()->json($budget);
    }

    public function destroy(Category $category)
    {
        $user = auth()->user();
        $budget = $user->budgets()->where('category_id', $category->id)->firstOrFail();
        $budget->delete();

        return response()->json(['message' => 'Budget deleted successfully.']);
    }


}