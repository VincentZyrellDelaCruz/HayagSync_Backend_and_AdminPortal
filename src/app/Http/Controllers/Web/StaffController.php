<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('users.staffs.index');
    }

    public function getStaff()
    {
        $users = User::with(['staff.positions'])->whereHas('staff')->select('users.*');

        return DataTables::of($users)
            ->addColumn('staff_number', function ($user) {
                return $user->staff && $user->staff->staff_number
                    ? $user->staff->staff_number
                    : 'Unknown';
            })
            ->addColumn('position', function ($user) {
                if (!$user->staff || $user->staff->positions->isEmpty()) {
                    return 'Unknown';
                }

                $latestPosition = $user->staff->positions
                    ->sortByDesc('pivot.assigned_at')
                    ->first();

                return $latestPosition ? $latestPosition->position_name : 'Unknown';
            })
            ->addColumn('department', function ($user) {
                return $user->staff && $user->staff->department
                    ? $user->staff->department
                    : 'Unknown';
            })
            ->addColumn('action', function () {
                return '<div class="d-flex gap-1">
                    <a href=""
                        class="inline-flex items-center rounded-md px-2 py-1 bg-gray-100 text-gray-700 hover:bg-gray-200"
                        title="Edit">
                        <i class="bi bi-pencil text-[13px]"></i>
                    </a>

                    <a href=""
                        class="inline-flex items-center rounded-md px-2 py-1 bg-gray-100 text-gray-700 hover:bg-gray-200"
                        title="Update Status">
                        <i class="bi bi-shield-fill text-[13px]"></i>
                    </a>

                    <a href=""
                        class="inline-flex items-center rounded-md px-2 py-1 bg-gray-100 text-red-600 hover:bg-gray-200"
                        title="Deactivate">
                        <i class="bi bi-person-x-fill text-[13px]"></i>
                    </a>

                </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
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
