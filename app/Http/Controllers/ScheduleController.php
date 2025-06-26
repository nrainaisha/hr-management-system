<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScheduleController extends Controller
{

    public function admin() {
        // Fetch all schedules, staff, etc.
        return Inertia::render('Schedule/AdminSchedule');
    }

    public function employee() {
        // Fetch only the logged-in employee's schedule
        return Inertia::render('Schedule/MySchedule');
    }
} 