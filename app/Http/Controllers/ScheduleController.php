<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\Schedule;

class ScheduleController extends Controller
{

    public function admin() {
        $staffList = Employee::whereDoesntHave('roles', function($q) {
            $q->where('name', 'owner');
        })->select('id', 'name')->get();
        // You can also filter by role/active status if needed
        return Inertia::render('Schedule/AdminSchedule', [
            'staffList' => $staffList,
        ]);
    }

    public function employee() {
        // Fetch only the logged-in employee's schedule
        return Inertia::render('Schedule/MySchedule');
    }

    // Assign a staff to a shift (create or update)
    public function assign(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'shift_type' => 'required|in:morning,evening',
            'week_start' => 'required|date',
            'day' => 'required|date',
        ]);

        // Update or create the schedule assignment
        $schedule = Schedule::updateOrCreate(
            [
                'shift_type' => $validated['shift_type'],
                'week_start' => $validated['week_start'],
                'day' => $validated['day'],
            ],
            [
                'employee_id' => $validated['employee_id'],
            ]
        );

        return response()->json(['success' => true, 'schedule' => $schedule]);
    }

    // Fetch all assignments for a given week
    public function week(Request $request)
    {
        $weekStart = $request->query('week_start');
        if (!$weekStart) {
            return response()->json(['error' => 'week_start is required'], 400);
        }
        $schedules = Schedule::with('employee')
            ->where('week_start', $weekStart)
            ->get();

        // Format assignments for frontend: { 'YYYY-MM-DD': [morning_employee_id, evening_employee_id] }
        $assignments = [];
        $submitted = false;
        foreach ($schedules as $schedule) {
            $day = $schedule->day;
            if (!isset($assignments[$day])) {
                $assignments[$day] = [null, null];
            }
            $idx = $schedule->shift_type === 'morning' ? 0 : 1;
            $assignments[$day][$idx] = $schedule->employee_id;
            if ($schedule->submitted) {
                $submitted = true;
            }
        }
        return response()->json(['assignments' => $assignments, 'submitted' => $submitted]);
    }

    // Reset all assignments for a given week
    public function reset(Request $request)
    {
        $request->validate([
            'week_start' => 'required|date',
        ]);
        \App\Models\Schedule::where('week_start', $request->week_start)->delete();
        return response()->json(['success' => true]);
    }

    public function assignTask() {
        return Inertia::render('Schedule/AssignTask');
    }

    public function myWeek(Request $request)
    {
        $user = $request->user();
        $weekStart = $request->query('week_start');
        if (!$weekStart) {
            return response()->json(['error' => 'week_start is required'], 400);
        }
        $schedules = \App\Models\Schedule::where('week_start', $weekStart)
            ->where('employee_id', $user->id)
            ->get();

        // Format: { 'YYYY-MM-DD': { morning: {start_time, end_time}, night: {...} } }
        $assignments = [];
        foreach ($schedules as $schedule) {
            $day = $schedule->day;
            $type = $schedule->shift_type; // 'morning' or 'night'
            if (!isset($assignments[$day])) {
                $assignments[$day] = ['morning' => null, 'evening' => null];
            }
            $assignments[$day][$type] = [
                'name' => ucfirst($type),
                'start_time' => $type === 'morning' ? '06:00' : '15:00',
                'end_time' => $type === 'morning' ? '15:00' : '00:00',
            ];
        }
        return response()->json(['assignments' => $assignments]);
    }

    public function viewTask() {
        return Inertia::render('Schedule/ViewTask');
    }

    // Mark a week as submitted
    public function submitWeek(Request $request)
    {
        $request->validate([
            'week_start' => 'required|date',
        ]);
        \App\Models\Schedule::where('week_start', $request->week_start)->update(['submitted' => true]);
        return response()->json(['success' => true]);
    }

    public function day(Request $request)
    {
        $date = $request->query('date');
        if (!$date) {
            return response()->json(['error' => 'date is required'], 400);
        }
        $schedules = \App\Models\Schedule::with('employee')->where('day', $date)->get();
        $assignments = [0 => null, 1 => null, '0_id' => null, '1_id' => null];
        foreach ($schedules as $schedule) {
            $idx = $schedule->shift_type === 'morning' ? 0 : 1;
            $assignments[$idx] = $schedule->employee ? [
                'id' => $schedule->employee->id,
                'name' => $schedule->employee->name
            ] : null;
            $assignments[$idx . '_id'] = $schedule->id;
        }
        return response()->json(['assignments' => $assignments]);
    }
} 