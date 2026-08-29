<?php

namespace App\Http\Controllers;

use App\Models\GradeSection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'parent_guardian');
        $search = $request->get('search');

        $query = User::query();

        $authUser = Auth::user();

        $authPositions = $authUser->staff?->positions->pluck('position_name')->toArray() ?? [];

        $isAdmin = (bool) ($authUser->staff?->is_admin);

        $isTeacher = in_array('Teacher', $authPositions);

        $isManagement = (bool) array_intersect($authPositions,['Principal', 'OSD Officer', 'Ministrong Tagasubaybay']);

        /*
        |--------------------------------------------------------------------------
        | Role-based restrictions
        |--------------------------------------------------------------------------
        |
        | Access rules:
        |
        | 1. Admin staff -> Can view ALL user lists, including administrators.
        |
        | 2. Principal / OSD Officer / Ministrong Tagasubaybay -> Can view all non-administrator users.
        |
        | 3. Teacher / Adviser -> Can view Parent / Guardian users only, restricted to
        |    parents/guardians connected to their advised sections.
        |
        |--------------------------------------------------------------------------
        */

        switch ($filter) {

            case 'parent_guardian':
                $query->with('parent_guardian')->whereHas('parent_guardian');

                // Teacher / Adviser restriction
                if ($isTeacher && !$isManagement && !$isAdmin) {

                    $sectionIds = GradeSection::where('adviser', $authUser->staff->id)->pluck('id')->toArray();

                    if (!empty($sectionIds)) {
                        $query->whereHas('parent_guardian.students', function ($q) use ($sectionIds) {
                            $q->whereIn('grade_section_id', $sectionIds);
                        });

                    }
                    else {
                        // Teacher has no assigned/advised sections.
                        $query->whereRaw('0 = 1');
                    }
                }

                break;

            case 'staff':
                if (!$isManagement && !$isAdmin) {
                    abort(403, 'Unauthorized to view staff list');
                }

                $query->with(['staff.positions'])->whereHas('staff', function ($q) {
                    /*
                        * A staff member is considered an administrator
                        * only when is_admin is explicitly true.
                        *
                        * Therefore NULL and false are both treated
                        * as non-administrative staff.
                        */
                    $q->where(function ($q) {
                        $q->where('is_admin', false)->orWhereNull('is_admin');
                    });
                });

                break;

            case 'admin':
                if (!$isAdmin) {
                    abort(403, 'Unauthorized to view admin list');
                }

                $query->with(['staff.positions'])->whereHas('staff', function ($q) {
                    $q->where('is_admin', true);
                });

                break;

            case 'teacher':
                if (!$isManagement && !$isAdmin) {
                    abort(403, 'Unauthorized to view teacher list');
                }

                $query->with(['staff.positions'])->whereHas('staff', function ($q) {
                        /*
                         * Teachers who are administrators belong only
                         * in the Administrators list, not this list.
                         */
                        $q->where(function ($q) {
                            $q->where('is_admin', false)->orWhereNull('is_admin');
                        });
                    })->whereHas('staff.positions', function ($q) {
                        $q->where('position_name', 'Teacher');
                    });

                break;

            case 'principal_ministro':
                if (!$isManagement && !$isAdmin) {
                    abort(403, 'Unauthorized to view teacher list');
                }

                $query->with(['staff.positions'])->whereHas('staff', function ($q) {
                        /*
                         * Teachers who are administrators belong only
                         * in the Administrators list, not this list.
                         */
                        $q->where(function ($q) {
                            $q->where('is_admin', false)->orWhereNull('is_admin');
                        });
                    })->whereHas('staff.positions', function ($q) {
                        $q->where('position_name', 'Principal')->orWhere('position_name', 'Ministrong Tagasubaybay');
                    });

                break;

            case 'osd_officer':
                if (!$isManagement && !$isAdmin) {
                    abort(403, 'Unauthorized to view teacher list');
                }

                $query
                    ->with(['staff.positions'])->whereHas('staff', function ($q) {
                        $q->where(function ($q) {
                            $q->where('is_admin', false)
                                ->orWhereNull('is_admin');
                        });
                    })->whereHas('staff.positions',function ($q) {
                        $q->where('position_name', 'OSD Officer');
                    });

                break;

            // Invalid filter
            default:
                abort(404, 'Invalid user filter');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(10);

        return Inertia::render('Users/Index', compact('users', 'filter', 'search'));
    }

    public function show(User $user)
    {
        $user->load([
            'staff.positions',
            'staff.section_advisers.school_year',
            'parent_guardian.students',
        ]);

        return Inertia::render('Users/UserInfo', compact('user'));
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
