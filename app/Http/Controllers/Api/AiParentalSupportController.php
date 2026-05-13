<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiGuidance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiParentalSupportController extends Controller
{
    public function index(): JsonResponse
    {
        $parenting_tips_list = AiGuidance::whereHas('incident', function ($query) {
            $query->where('reported_by', Auth::id());
        })
        ->get(['id', 'tips_title', 'created_at']);

        return response()->json($parenting_tips_list);
    }

    public function show(String $id): JsonResponse
    {
        $parenting_tips_list = AiGuidance::whereHas('incident', function ($query) {
            $query->where('reported_by', Auth::id());
        })
        ->findOrFail($id);

        return response()->json($parenting_tips_list);
    }
}
