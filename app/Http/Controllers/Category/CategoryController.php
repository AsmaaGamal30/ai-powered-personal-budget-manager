<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        return response()->json($categories);
    }

    public function store(Category $category)
    {
        $user = auth()->user();
        $user->categories()->attach($category->id);

        return response()->json(['message' => 'Category added to user successfully.'], Response::HTTP_CREATED);
    }

}
