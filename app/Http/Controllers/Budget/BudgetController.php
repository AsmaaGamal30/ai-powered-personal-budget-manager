<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BudgetController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $budgets = $user->budgets()->with(['category'])->get();
        return response()->json($budgets);
    }

    public function store(Category $category, Request $request)
    {
        $user = auth()->user();
        $budget = $user->budgets()->create([
            'name' => $request->name,
            'category_id' => $category->id,
            'amount' => $request->amount,
        ]);

        return response()->json($budget, 201);
    }

    public function update(Category $category, Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'name' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $budget = $user->budgets()->where('category_id', $category->id)->firstOrFail();
        $budget->update([
            'amount' => $request->amount,
            'name' => $request->name ?? $budget->name,
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
