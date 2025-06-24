<?php

namespace Database\Seeders;

use App\Models\Addition;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Calendar;
use App\Models\Deduction;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use App\Models\EmployeePosition;
use App\Models\EmployeeSalary;
use App\Models\EmployeeShift;
use App\Models\Globals;
use App\Models\Manager;
use App\Models\Metric;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\Request;
use App\Models\Shift;
use App\Services\CommonServices;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Globals
        $this->seedGlobals();

        // Branches, Departments, Positions, Shifts
        $this->seedBranchesDepartmentsPositionsShifts();

        // Create Admin and Example Employees
        $root = Employee::factory()->create([
            'name' => 'Ahmad Razif',
            'email' => 'ahmad@myhrsolutions.my',
            'phone' => '0123456789',
            'national_id' => '900101145522',
            'hired_on' => '2024-01-01',
            'password' => bcrypt('password'),
        ]);

        $emp = Employee::factory()->create([
            'name' => 'Nur Aisyah',
            'email' => 'aisyah@myhrsolutions.my',
            'phone' => '0171234567',
            'national_id' => '910202085522',
            'hired_on' => '2024-02-01',
            'password' => bcrypt('password'),
        ]);

        Employee::factory(14)->create();
        Metric::factory(5)->create();

        // Roles
        $roles = ['admin', 'employee'];
        foreach ($roles as $role) {
            Role::create(['name' => $role]);
        }

        $root->assignRole('admin');

        // Assign Positions, Shifts, Salaries
        foreach (Employee::all() as $index => $employee) {
            if ($employee->id != $root->id) {
                $employee->assignRole('employee');
            }

            EmployeePosition::create([
                'employee_id' => $employee->id,
                'position_id' => ($index % 4) + 1,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => null,
            ]);

            EmployeeShift::create([
                'employee_id' => $employee->id,
                'shift_id' => ($index % 2) + 1,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => null,
            ]);

            $currencies = ['MYR', 'USD', 'EUR', 'GBP', 'IDR'];
            EmployeeSalary::create([
                'employee_id' => $employee->id,
                'currency' => $currencies[array_rand($currencies)],
                'salary' => fake()->numberBetween(3000, 10000),
                'start_date' => now()->format('Y-m-d'),
                'end_date' => null,
            ]);

            $this->seedAttendance($employee);
        }

        // Assign Managers
        Manager::create([
            'employee_id' => $root->id,
            'branch_id' => 1,
            'department_id' => null,
        ]);

        Manager::create([
            'employee_id' => $emp->id,
            'branch_id' => null,
            'department_id' => 1,
        ]);

        // Payrolls, Requests, Calendar Items
        $this->generateRandomPayrolls();
        $this->seedRequests();
        $this->seedCalendarItems();
    }

    private function seedGlobals(): void
    {
        Globals::create([
            'organization_name' => 'Malaysia HR Solutions',
            'organization_address' => 'Lot 88, Jalan Ampang, Kuala Lumpur, Malaysia',
            'absence_limit' => 30,
            'email' => 'info@myhrsolutions.my',
        ]);
    }

    private function seedBranchesDepartmentsPositionsShifts(): void
    {
        Branch::factory()->create(['name' => 'Kuala Lumpur HQ']);
        Branch::factory()->create(['name' => 'Penang Branch']);

        Department::create(['name' => 'Human Resources']);
        Department::create(['name' => 'Finance']);
        Department::create(['name' => 'IT & Development']);
        Department::create(['name' => 'Customer Care']);

        Position::create([
            'name' => 'CEO',
            'description' => 'Chief Executive Officer',
        ]);
        Position::create([
            'name' => 'Operations Manager',
            'description' => 'Handles daily operations of the company',
        ]);
        Position::create([
            'name' => 'Software Engineer',
            'description' => 'Responsible for system development and support',
        ]);
        Position::create([
            'name' => 'Sales Executive',
            'description' => 'Manages sales and customer engagement',
        ]);

        Shift::create([
            'name' => "Morning Shift",
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);
        Shift::create([
            'name' => "Evening Shift",
            'start_time' => '16:00:00',
            'end_time' => '00:00:00',
        ]);
    }

    private function seedAttendance(Employee $employee): void
    {
        $commonServices = new CommonServices();
        $currentDate = CarbonImmutable::now();
        $startDate = $currentDate->startOfMonth();
        $days = $currentDate->diffInDays($startDate);

        for ($i = 0; $i < $days; $i++) {
            if (!$commonServices->isDayOff($startDate->format('Y-m-d'))) {
                Attendance::create([
                    'employee_id' => $employee->id,
                    'date' => $startDate->format('Y-m-d'),
                    'status' => fake()->randomElement(['on_time', 'missed']),
                    'sign_in_time' => $employee->activeShift()?->start_time ?? '09:00:00',
                    'sign_off_time' => $employee->activeShift()?->end_time ?? '17:00:00',
                ]);
            }
            $startDate = $startDate->addDay();
        }
    }

    private function generateRandomPayrolls(): void
    {
        foreach (Employee::all() as $employee) {
            $payroll = Payroll::factory()->create([
                'employee_id' => $employee->id,
                'due_date' => Carbon::now()->toDateString(),
            ]);

            foreach (Metric::all() as $metric) {
                EmployeeEvaluation::create([
                    'employee_id' => $employee->id,
                    'metric_id' => $metric->id,
                    'payroll_id' => $payroll->id,
                    'date' => Carbon::now()->toDateString(),
                ]);
            }

            Addition::factory()->create([
                'payroll_id' => $payroll->id,
                'due_date' => Carbon::now()->toDateString(),
            ]);

            Deduction::factory()->create([
                'payroll_id' => $payroll->id,
                'due_date' => Carbon::now()->toDateString(),
            ]);
        }
    }

    private function seedRequests(): void
    {
        foreach (Employee::all() as $employee) {
            Request::create([
                'employee_id' => $employee->id,
                'type' => fake()->randomElement(['leave', 'payment', 'complaint', 'other']),
                'start_date' => Carbon::now()->addDays(rand(1, 15))->toDateString(),
                'end_date' => null,
                'message' => fake()->sentence(10),
                'status' => fake()->numberBetween(0, 2),
                'admin_response' => fake()->sentence(10),
                'is_seen' => fake()->boolean,
            ]);
        }
    }

    private function seedCalendarItems(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $startDate = Carbon::now()->addDays(rand(1, 30));
            Calendar::create([
                'start_date' => $startDate->toDateString(),
                'end_date' => $startDate->addDays(rand(0, 2))->toDateString(),
                'title' => fake()->sentence(3),
                'type' => fake()->randomElement(['holiday', 'meeting', 'event']),
            ]);
        }
    }
}
