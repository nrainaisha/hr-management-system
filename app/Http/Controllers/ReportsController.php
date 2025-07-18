<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use Carbon\Carbon;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $year = substr($month, 0, 4);
        $monthNum = substr($month, 5, 2);
        $staffId = $request->input('staff_id');

        $employeeQuery = Employee::query();
        if ($staffId) {
            $employeeQuery->where('id', $staffId);
        }
        $employees = $employeeQuery->get()->map(function ($employee) use ($year, $monthNum) {
            $stats = $employee->myStats();
            $attended = $employee->attendances()->whereYear('date', $year)->whereMonth('date', $monthNum)->where('status', '!=', 'missed')->count();
            $absented = $employee->attendances()->whereYear('date', $year)->whereMonth('date', $monthNum)->where('status', 'missed')->count();
            $late = $employee->attendances()->whereYear('date', $year)->whereMonth('date', $monthNum)->where('status', 'late')->count();
            $onTime = $employee->attendances()->whereYear('date', $year)->whereMonth('date', $monthNum)->where('status', 'on_time')->count();
            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'attended' => $attended,
                'absented' => $absented,
                'late' => $late,
                'on_time' => $onTime,
                'stats' => $stats,
            ];
        });

        $allStaff = Employee::select('id', 'name')->get();

        return Inertia::render('Reports/Reports', [
            'employees' => $employees,
            'month' => $month,
            'allStaff' => $allStaff,
            'selectedStaffId' => $staffId,
        ]);
    }
} 