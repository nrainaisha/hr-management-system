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
        foreach (Employee::all() as $employee) {
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
