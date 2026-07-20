<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'parent_guardian'); // default to parent_guardian
        $search = $request->get('search');

        $query = User::query();

        switch ($filter) {
            case 'parent_guardian':
                $query->with('parent_guardian')->whereHas('parent_guardian');
                break;
            case 'staff':
                $query->with('staff.positions')->whereHas('staff');
                break;
            case 'admin':
                $query->with('staff.positions')
                    ->whereHas('staff', fn($q) => $q->where('is_admin', true));
                break;
            case 'adviser':
                $query->with('staff.positions')
                    ->whereHas('staff.positions', fn($q) => $q->where('position_name', 'Adviser'));
                break;
            case 'tagasubaybay':
                $query->with('staff.positions')
                    ->whereHas('staff.positions', fn($q) => $q->where('position_name', 'Ministrong Tagasubaybay'));
                break;
            case 'principal':
                $query->with('staff.positions')
                    ->whereHas('staff.positions', fn($q) => $q->where('position_name', 'Principal'));
                break;
            case 'osd_officer':
                $query->with('staff.positions')
                    ->whereHas('staff.positions', fn($q) => $q->where('position_name', 'OSD Officer'));
                break;
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(10);

        return view('users.index', compact('users', 'filter'));
    }

    public function show(User $user)
    {
        $user->load([
            'staff.positions',
            'staff.section_advisers.school_year',
            'parent_guardian.students',
        ]);

        return view('users.user-info', compact('user'));
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

    public function getParentGuardian()
    {
        $users = User::with(['parent_guardian'])->whereHas('parent_guardian')->select('users.*');

        return DataTables::of($users)
            ->addColumn('parent_code', function ($user) {
                return $user->parent_guardian && $user->parent_guardian->parent_code
                    ? $user->parent_guardian->parent_code
                    : 'Unknown';
            })
            ->addColumn('occupation', function ($user) {
                return $user->parent_guardian && $user->parent_guardian->occupation
                    ? $user->parent_guardian->occupation
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
}
