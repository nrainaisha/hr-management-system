<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\EmployeeShift;
use App\Models\Globals;
use App\Models\Manager;
use App\Models\Metric;
use App\Models\Request;
use App\Models\Shift;
use App\Services\CommonServices;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Spatie\Permission\Models\Role;
use App\Models\Client;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Task;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Globals
        $this->seedGlobals();

        // Seed Shifts (must be before EmployeeShift)
        Shift::updateOrCreate([
            'name' => 'Morning Shift',
        ], [
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'shift_payment_multiplier' => 1.0,
            'description' => 'Standard morning shift',
        ]);
        Shift::updateOrCreate([
            'name' => 'Evening Shift',
        ], [
            'start_time' => '16:00:00',
            'end_time' => '00:00:00',
            'shift_payment_multiplier' => 1.2,
            'description' => 'Standard evening shift',
        ]);

        // Roles
        $roles = ['admin', 'employee'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Add owner role
        Role::firstOrCreate(['name' => 'owner']);

        // Create Admin (Siti Noraini)
        $admin = Employee::create([
            'name' => 'Siti Noraini Binti Ahmad',
            'email' => 'siti.noraini@myhrsolutions.my',
            'phone' => '013-9876543',
            'national_id' => '880202085522',
            'hired_on' => '2024-02-01',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        // Create Owner User
        $owner = Employee::create([
            'name' => 'Owner User',
            'email' => 'owner@myhrsolutions.my',
            'phone' => '019-8888888',
            'national_id' => '800101015522',
            'hired_on' => '2024-01-01',
            'password' => bcrypt('password'),
        ]);
        $owner->assignRole('owner');

        // Create 3 Employees
        $employee1 = Employee::create([
            'name' => 'Ahmad Razif Bin Ismail',
            'email' => 'ahmad.razif@myhrsolutions.my',
            'phone' => '012-3456789',
            'national_id' => '900101145522',
            'hired_on' => '2024-01-01',
            'password' => bcrypt('password'),
        ]);
        $employee1->assignRole('employee');

        $employee2 = Employee::create([
            'name' => 'Muhammad Faiz Bin Abdullah',
            'email' => 'faiz.abdullah@myhrsolutions.my',
            'phone' => '014-2233445',
            'national_id' => '950303105522',
            'hired_on' => '2024-03-01',
            'password' => bcrypt('password'),
        ]);
        $employee2->assignRole('employee');

        $employee3 = Employee::create([
            'name' => 'Lim Wei Jie',
            'email' => 'lim.weijie@myhrsolutions.my',
            'phone' => '016-5566778',
            'national_id' => '970404125522',
            'hired_on' => '2024-04-01',
            'password' => bcrypt('password'),
        ]);
        $employee3->assignRole('employee');

        // Assign Shifts and Salaries
        $employees = [$admin, $employee1, $employee2, $employee3];
        foreach ($employees as $index => $employee) {
            EmployeeShift::create([
                'employee_id' => $employee->id,
                'shift_id' => ($index % 2) + 1,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => null,
            ]);
            EmployeeSalary::create([
                'employee_id' => $employee->id,
                'currency' => 'MYR',
                'salary' => 5000 + ($index * 500),
                'start_date' => now()->format('Y-m-d'),
                'end_date' => null,
            ]);
        }

        // Assign Managers (admin only)
        Manager::create([
            'employee_id' => $admin->id,
        ]);

        // Requests, Calendar Items
        $this->seedRequests();
        $this->seedCalendarItems();

        // Seed complete attendance for every employee for July 1-22 (working days only)
        $start = Carbon::create(null, 7, 1);
        $end = Carbon::create(null, 7, 22);
        $commonServices = new CommonServices();
        foreach (Employee::all() as $employee) {
            $date = $start->copy();
            while ($date->lte($end)) {
                if (!$commonServices->isDayOff($date->toDateString())) {
                    Attendance::create([
                        'employee_id' => $employee->id,
                        'date' => $date->toDateString(),
                        'status' => 'on_time',
                        'sign_in_time' => '08:00:00',
                        'sign_off_time' => '17:00:00',
                        'notes' => 'Seeded for demo',
                    ]);
                }
                $date->addDay();
            }
        }

        // Seed random clients for each employee
        foreach ([$employee1, $employee2, $employee3] as $employee) {
            // Removed shift assignment
            // Seed random clients for each employee
            $clientCount = rand(3, 8);
            for ($i = 0; $i < $clientCount; $i++) {
                Client::create([
                    'employee_id' => $employee->id,
                    'name' => fake()->name(),
                    'contact_info' => fake()->email(),
                ]);
            }
        }

        // Truncate schedules and tasks
        Schedule::truncate();
        Task::truncate();

        // Truncate employee leaves
        \App\Models\EmployeeLeave::truncate();

        $start = Carbon::parse('2025-06-30');
        $end = Carbon::parse('2025-07-22');
        $taskOptions = [
            [
                'title' => 'Cleaning',
                'descriptions' => ['Bathroom cleaning', 'Equipment cleaning', 'Floor Cleaning']
            ],
            [
                'title' => 'Services',
                'descriptions' => ['Weekly services']
            ],
            [
                'title' => 'Inspection',
                'descriptions' => ['Back Machine', 'Chest Machine', 'Cardio equipment', 'Dumbbell', 'Leg Machine', 'Arm Machine']
            ],
            [
                'title' => 'Counter',
                'descriptions' => ['Manage Registration']
            ]
        ];
        $shiftTypes = ['morning', 'evening'];

        $employees = Employee::whereDoesntHave('roles', function($q) {
            $q->where('name', 'owner');
        })->get();
        $employeeCount = $employees->count();
        $employeeIds = $employees->pluck('id')->toArray();

        $allSlots = [];
        $date = $start->copy();
        while ($date->lte($end)) {
            foreach ($shiftTypes as $shiftType) {
                $allSlots[] = [
                    'date' => $date->toDateString(),
                    'shift_type' => $shiftType,
                    'week_start' => $date->copy()->startOfWeek()->toDateString(),
                ];
            }
            $date->addDay();
        }

        // Assign slots round-robin
        $slotAssignments = [];
        foreach ($allSlots as $i => $slot) {
            $employeeIdx = $i % $employeeCount;
            $employeeId = $employeeIds[$employeeIdx];
            $slotAssignments[] = array_merge($slot, ['employee_id' => $employeeId]);
        }

        // Create schedules and tasks
        foreach ($slotAssignments as $slot) {
            $schedule = Schedule::create([
                'employee_id' => $slot['employee_id'],
                'shift_type' => $slot['shift_type'],
                'week_start' => $slot['week_start'],
                'day' => $slot['date'],
            ]);
            $taskOpt = $taskOptions[array_rand($taskOptions)];
            $title = $taskOpt['title'];
            $description = $taskOpt['descriptions'][array_rand($taskOpt['descriptions'])];
            // Status logic
            if ($slot['employee_id'] == 2) {
                $status = 'completed';
            } elseif ($slot['employee_id'] == 3) {
                $status = rand(1, 10) <= 9 ? 'completed' : 'not_completed';
            } else {
                $status = rand(1, 10) <= 6 ? 'completed' : 'not_completed';
            }
            Task::create([
                'schedule_id' => $schedule->id,
                'title' => $title,
                'description' => $description,
                'status' => $status,
            ]);
        }

        // Set leave balances for all staff (excluding owner)
        $staff = Employee::whereDoesntHave('roles', function($q) {
            $q->where('name', 'owner');
        })->get();
        foreach ($staff as $employee) {
            \App\Models\EmployeeLeave::updateOrCreate(
                ['employee_id' => $employee->id, 'leave_type' => 'Annual Leave'],
                ['balance' => 10]
            );
            \App\Models\EmployeeLeave::updateOrCreate(
                ['employee_id' => $employee->id, 'leave_type' => 'Emergency Leave'],
                ['balance' => 5]
            );
        }
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

    private function seedAttendance(Employee $employee): void
    {
        $commonServices = new CommonServices();
        $currentDate = CarbonImmutable::now();
        $startDate = $currentDate->startOfMonth();
        $days = $currentDate->diffInDays($startDate);

        for ($i = 0; $i < $days; $i++) {
            if (!$commonServices->isDayOff($startDate->format('Y-m-d'))) {
                // You can add attendance logic here if needed
            }
            $startDate = $startDate->addDay();
        }
    }

    private function seedRequests(): void
    {
        $employees = Employee::whereDoesntHave('roles', function($q) {
            $q->where('name', 'owner');
        })->get();
        foreach ($employees as $employee) {
            Request::create([
                'employee_id' => $employee->id,
                'type' => fake()->randomElement(['Annual Leave', 'Emergency Leave', 'Sick Leave']),
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
            // Add your Calendar seeding logic here if needed
        }
    }
}
