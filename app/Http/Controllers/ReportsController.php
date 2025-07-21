<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use Carbon\Carbon;
use App\Models\Payroll;
use App\Models\Task;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $year = substr($month, 0, 4);
        $monthNum = substr($month, 5, 2);
        $staffId = $request->input('staff_id');

        $employeeQuery = Employee::whereDoesntHave('roles', function($q) {
            $q->where('name', 'owner');
        });
        if ($staffId) {
            $employeeQuery->where('id', $staffId);
        }
        $employees = $employeeQuery->get();

        // Attendance, task, client stats
        $employeeStats = $employees->map(function ($employee) use ($year, $monthNum) {
            $attended = $employee->attendances()->whereYear('date', $year)->whereMonth('date', $monthNum)->where('status', '!=', 'missed')->count();
            $absented = $employee->attendances()->whereYear('date', $year)->whereMonth('date', $monthNum)->where('status', 'missed')->count();
            $late = $employee->attendances()->whereYear('date', $year)->whereMonth('date', $monthNum)->where('status', 'late')->count();
            $onTime = $employee->attendances()->whereYear('date', $year)->whereMonth('date', $monthNum)->where('status', 'on_time')->count();

            if ($employee->id == 1) {
                // Admin/supervisor: mark as non-applicable
                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'attended' => $attended,
                    'absented' => $absented,
                    'late' => $late,
                    'on_time' => $onTime,
                    'task_completed' => 0,
                    'task_total' => 0,
                    'task_label' => 'Non-applicable',
                    'clients' => 0,
                ];
            }

            // Task completion
            $taskTotal = Task::whereHas('schedule', function($q) use ($employee, $year, $monthNum) {
                $q->where('employee_id', $employee->id)
                  ->whereYear('day', $year)
                  ->whereMonth('day', $monthNum);
            })->count();
            $taskCompleted = Task::whereHas('schedule', function($q) use ($employee, $year, $monthNum) {
                $q->where('employee_id', $employee->id)
                  ->whereYear('day', $year)
                  ->whereMonth('day', $monthNum);
            })->where('status', 'completed')->count();

            // Client count
            $clientCount = $employee->clients()->count();

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'attended' => $attended,
                'absented' => $absented,
                'late' => $late,
                'on_time' => $onTime,
                'task_completed' => $taskCompleted,
                'task_total' => $taskTotal,
                'clients' => $clientCount,
            ];
        });

        // Total salary cost (current salary for all employees, excluding owner)
        $totalSalaryCost = Employee::whereDoesntHave('roles', function($q) {
            $q->where('name', 'owner');
        })->get()->sum(function($employee) {
            $salary = $employee->salary();
            return $salary[1] ?? 0;
        });

        // Summary cards
        $summary = [
            'employees' => Employee::whereDoesntHave('roles', function($q) {
                $q->where('name', 'owner');
            })->count(),
            'attendance_rate' => $employeeStats->avg(function($e) {
                $total = $e['attended'] + $e['absented'] + $e['late'];
                return $total ? ($e['attended'] / $total) * 100 : 0;
            }),
            'payroll_cost' => $totalSalaryCost, // Use total salary cost, not payroll sum
            'top_staff' => $employeeStats->sortByDesc('clients')->first()['name'] ?? null,
        ];

        $allStaff = Employee::whereDoesntHave('roles', function($q) {
            $q->where('name', 'owner');
        })->select('id', 'name')->get();

        return Inertia::render('Reports/Reports', [
            'employeeStats' => $employeeStats,
            'summary' => $summary,
            'month' => $month,
            'allStaff' => $allStaff,
            'selectedStaffId' => $staffId,
        ]);
    }
} 