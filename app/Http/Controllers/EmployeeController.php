<?php

namespace App\Http\Controllers;

use App\Models\ArchivedEmployee;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Services\EmployeeServices;
use App\Services\ValidationServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    protected EmployeeServices $employeeServices;
    protected ValidationServices $validationServices;
    public function __construct()
    {
        $this->employeeServices = new EmployeeServices;
        $this->validationServices = new ValidationServices;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sortDir = 'asc';
        if ($request->has('sort')) {
            $request->validate([
                'sort' => 'in:id,name',
                'sort_dir' => 'required|boolean',
            ]);
            $sortDir = $request->sort_dir ? 'asc' : 'desc';
        }

        $employees = Employee::whereDoesntHave('roles', function($q) {
            $q->where('name', 'owner');
        })
        ->when($request->term, function ($query, $term) {
                $query->where('normalized_name', 'ILIKE', '%' . normalizeArabic($term) . '%')
                    ->orWhere('email', 'ILIKE', '%' . $term . '%')
                    ->orWhere('id', 'ILIKE', '%' . $term . '%')
                    ->orWhere('phone', 'ILIKE', '%' . $term . '%')
                    ->orWhere('national_id', 'ILIKE', '%' . $term . '%');
            })->orderBy($request->sort ?? 'id', $sortDir)->select(['id', 'name', 'email', 'phone', 'national_id'])
            ->paginate(config('constants.data.pagination_count'));

        return Inertia::render('Employee/Employees', [
            'employees' => $employees,
        ]);
    }

    public function archivedIndex(Request $request)
    {
        $sortDir = 'asc';
        if ($request->has('sort')) {
            $request->validate([
                'sort' => 'in:id,name',
                'sort_dir' => 'required|boolean',
            ]);
            $sortDir = $request->sort_dir ? 'asc' : 'desc';
        }

        $employees = ArchivedEmployee::whereDoesntHave('roles', function($q) {
            $q->where('name', 'owner');
        })
        ->when($request->term, function ($query, $term) {
                $query->where('name', 'ILIKE', '%' . $term . '%')
                    ->orWhere('email', 'ILIKE', '%' . $term . '%')
                    ->orWhere('id', 'ILIKE', '%' . $term . '%')
                    ->orWhere('phone', 'ILIKE', '%' . $term . '%')
                    ->orWhere('national_id', 'ILIKE', '%' . $term . '%');
            })->orderBy($request->sort ?? 'released_on', $sortDir)
                ->select(['id', 'name', 'email', 'phone', 'national_id', 'hired_on', 'released_on'])
            ->paginate(config('constants.data.pagination_count'));

        return Inertia::render('Employee/ArchievedEmployees', [
            'employees' => $employees,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Employee/EmployeeCreate', [
            'roles' => Role::whereIn('name', ['admin', 'employee'])->get(['id', 'name']),
            'shifts' => Shift::get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate Input Firsts
        $res = $this->validationServices->validateEmployeeCreationDetails($request);
        // Employee Registration
        return $this->employeeServices->createEmployee($res);
    }

    // A function without parameters
    public function showMyProfile()
    {
        return $this->show(auth()->user()->id);
    }

    public function show(string $id): Response
    {
        return Inertia::render('Employee/EmployeeView', [
            'employee' => Employee::with(["salaries", "roles", 'employeeShifts.shift', 'manages'])
                ->where('employees.id', $id)
                ->select('employees.id', 'employees.name', 'employees.phone', 'employees.national_id', 'employees.email',
                    'employees.address', 'employees.bank_acc_no', 'employees.hired_on')
                ->first(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return Inertia::render('Employee/EmployeeEdit', [
            'employee' => Employee::with(["salaries", "roles", 'employeeShifts.shift'])
                ->where('employees.id', $id)
                ->select('employees.id', 'employees.name', 'employees.phone', 'employees.national_id', 'employees.email',
                    'employees.address', 'employees.bank_acc_no', 'employees.hired_on')
                ->first(),
            'roles' => Role::whereIn('name', ['admin', 'employee'])->get(['id', 'name']),
            'shifts' => Shift::get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate Input Firsts
        $res = $this->validationServices->validateEmployeeUpdateDetails($request, $id);

        // Update Employee
        return $this->employeeServices->updateEmployee(Employee::findOrFail($id), $res);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return $this->employeeServices->deleteEmployee($id);
    }

    public function find(Request $request)
    {
        return Inertia::render('Employee/EmployeeFind', [
            'employees' => Employee::when($request->term, function ($query, $term) {
                $query
                    ->where('name', 'ILIKE', '%' . $term . '%')
                    ->orWhere('id', 'ILIKE', '%' . $term . '%')
                    ->orWhere('email', 'ILIKE', '%' . $term . '%')
                    ->orWhere('phone', 'ILIKE', '%' . $term . '%')
                    ->orWhere('national_id', 'ILIKE', '%' . $term . '%');
            })->get(),
        ]);
    }
}
