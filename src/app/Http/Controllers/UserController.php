<?php

namespace App\Http\Controllers;

use App\Models\GradeSection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'parent_guardian');
        $search = trim((string) $request->get('search', ''));

        $authUser = Auth::user();
        $authStaff = $authUser->staff;

        $authPositions = $authStaff
            ? $authStaff->positions()->pluck('positions.position_name')->all()
            : [];

        $isAdmin = (bool) ($authStaff?->is_admin);
        $isTeacher = in_array('Teacher', $authPositions, true);
        $isManagement = (bool) array_intersect(
            $authPositions,
            ['Principal', 'OSD Officer', 'Ministrong Tagasubaybay']
        );

        $query = User::query()->select([
            'users.id',
            'users.first_name',
            'users.last_name',
            'users.middle_name',
            'users.suffix',
            'users.email',
            'users.phone_number',
            'users.profile_image_url',
            'users.status',
            'users.created_at',
        ]);

        switch ($filter) {
            case 'parent_guardian':
                $query
                    ->with('parent_guardian:user_id,parent_code,occupation')
                    ->whereHas('parent_guardian');

                if ($isTeacher && !$isManagement && !$isAdmin) {
                    $sectionIds = GradeSection::query()
                        ->where('adviser', $authStaff->user_id)
                        ->pluck('id');

                    if ($sectionIds->isEmpty()) {
                        $query->whereRaw('0 = 1');
                    } else {
                        $query->whereHas('parent_guardian.students.enrollments', function ($q) use ($sectionIds) {
                            $q->whereIn('grade_section_id', $sectionIds)
                                ->where('status', 'Enrolled')
                                ->whereNull('ended_at');
                        });
                    }
                }

                break;

            case 'staff':
                if (!$isManagement && !$isAdmin) {
                    abort(403, 'Unauthorized to view staff list');
                }

                $query
                    ->with([
                        'staff:user_id,staff_number,is_admin',
                        'staff.positions:id,position_name,department',
                    ])
                    ->whereHas('staff', function ($q) {
                        /*
                        | A staff member is considered an administrator
                        | only when is_admin is explicitly true.
                        |
                        | Therefore NULL and false are both treated
                        | as non-administrative staff.
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

                $query
                    ->with([
                        'staff:user_id,staff_number,is_admin',
                        'staff.positions:id,position_name,department',
                    ])
                    ->whereHas('staff', fn ($q) => $q->where('is_admin', true));

                break;

            case 'teacher':
                if (!$isManagement && !$isAdmin) {
                    abort(403, 'Unauthorized to view teacher list');
                }

                $query
                    ->with([
                        'staff:user_id,staff_number,is_admin',
                        'staff.positions:id,position_name,department',
                    ])
                    ->whereHas('staff', function ($q) {
                        /*
                         | Teachers who are administrators belong only
                         | in the Administrators list, not this list.
                         */
                        $q->where(function ($q) {
                            $q->where('is_admin', false)->orWhereNull('is_admin');
                        });
                    })
                    ->whereHas('staff.positions', fn ($q) => $q->where('position_name', 'Teacher'));

                break;

            case 'principal_ministro':
                if (!$isManagement && !$isAdmin) {
                    abort(403, 'Unauthorized to view teacher list');
                }

                $query
                    ->with([
                        'staff:user_id,staff_number,is_admin',
                        'staff.positions:id,position_name,department',
                    ])
                    ->whereHas('staff', function ($q) {
                        /*
                         | Teachers who are administrators belong only
                         | in the Administrators list, not this list.
                         */
                        $q->where(function ($q) {
                            $q->where('is_admin', false)->orWhereNull('is_admin');
                        });
                    })
                    ->whereHas('staff.positions', fn ($q) =>
                        $q->whereIn('position_name', ['Principal', 'Ministrong Tagasubaybay'])
                    );

                break;

            case 'osd_officer':
                if (!$isManagement && !$isAdmin) {
                    abort(403, 'Unauthorized to view teacher list');
                }

                $query
                    ->with([
                        'staff:user_id,staff_number,is_admin',
                        'staff.positions:id,position_name,department',
                    ])
                    ->whereHas('staff', function ($q) {
                        $q->where(function ($q) {
                            $q->where('is_admin', false)->orWhereNull('is_admin');
                        });
                    })
                    ->whereHas('staff.positions', fn ($q) => $q->where('position_name', 'OSD Officer'));

                break;

            default:
                abort(404, 'Invalid user filter');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Users/Index', compact('users', 'filter', 'search'));
    }

    public function show(User $user)
    {
        $user->load([
            'staff:user_id,staff_number,is_admin',
            'staff.positions:id,position_name,department',
            'staff.section_advisers.school_year:id,school_year',
            'parent_guardian.students:id,student_number,first_name,last_name,middle_name,suffix,status',
        ]);

        return Inertia::render('Users/UserInfo', compact('user'));
    }
}
