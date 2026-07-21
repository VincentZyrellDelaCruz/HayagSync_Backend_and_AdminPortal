<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');

        $query = ActivityLog::with('user');

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('action_type', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('module', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) =>
                      $uq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                  );
            });
        }

        $logs = $query->orderBy($sortField, $sortOrder)->paginate(10);

        return view('admin.activity-log', compact('logs', 'search', 'sortField', 'sortOrder'));
    }
}
