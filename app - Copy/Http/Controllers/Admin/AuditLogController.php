<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index()
    {
        $activities = Activity::with('causer')->latest()->paginate(25);
        return view('admin.audit_logs.index', compact('activities'));
    }
}
