<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncidentCategory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class IncidentCategoryController extends Controller
{
    public function showAllCategories(): JsonResponse
    {
        $categories = IncidentCategory::all();

        return response()->json($categories);
    }
}
