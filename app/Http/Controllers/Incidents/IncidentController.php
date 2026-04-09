<?php

namespace App\Http\Controllers\Incidents;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\IncidentCategory;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class IncidentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        $selectedStatus = $req->query('status', 'all');
        $selectedCategory = $req->query('category', 'all');

        $categories = IncidentCategory::where('is_active', true)->orderBy('category_name')->get();

        $incidentsQuery = Incident::with(['user', 'current_status', 'category']);

        if ($selectedStatus !== 'all') {
            $incidentsQuery->whereHas('current_status', function ($query) use ($selectedStatus) {
                $query->where('status_name', $selectedStatus);
            });
        }

        if ($selectedCategory !== 'all') {
            $incidentsQuery->where('category_id', $selectedCategory);
        }

        $allIncidents = $incidentsQuery
                ->get()
                ->sortBy([
                    fn ($incident) => match ($incident->current_status?->status_name) {
                        'Pending' => 1,
                        'Under Investigation' => 2,
                        'Scheduled' => 3,
                        'Resolved' => 4,
                        'Unresolved' => 5,
                        'Cancelled' => 6,
                        default => 99,
                    },

                    fn ($incident) => match ($incident->current_status?->status_name) {
                        'Pending' => 1,
                        'Under Investigation' => 2,
                        'Scheduled' => 3,
                        'Resolved' => 4,
                        'Unresolved' => 5,
                        'Cancelled' => 6,
                        default => 99,
                    },

                    fn ($incident) => -$incident->created_at->timestamp,
                ])
                ->values();

            $perPage = 10;
            $currentPage = LengthAwarePaginator::resolveCurrentPage();

            $currentItems = $allIncidents->slice(($currentPage -1) * $perPage, $perPage)->values();

            $incidents = new LengthAwarePaginator(
                $currentItems,
                $allIncidents->count(),
                $perPage,
                $currentPage,
                [
                    'path' => $req->url(),
                    'query' => $req->query(),
                ]
            );

            $statuses = ['all', 'Pending', 'Under Investigation' , 'Scheduled',
                    'Resolved', 'Unresolved', 'Cancelled'];

        return view('incidents.index', compact(
            'incidents',
            'categories',
            'statuses',
            'selectedStatus',
            'selectedCategory',
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
