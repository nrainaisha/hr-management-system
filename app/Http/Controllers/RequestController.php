<?php

namespace App\Http\Controllers;

use App\Services\RequestServices;
use App\Services\ValidationServices;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\EmployeeLeave;


// Using \App\Models\Request instead of Request because Request is a class in Illuminate\Http\Request
class RequestController extends Controller
{
    protected RequestServices $requestServices;
    protected ValidationServices $validationServices;
    public function __construct()
    {
        $this->requestServices = new RequestServices;
        $this->validationServices = new ValidationServices;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            // Admin sees all requests
        $requests = \App\Models\Request::query()
            ->join('employees', 'requests.employee_id', '=', 'employees.id')
            ->select(['requests.id', 'employees.name as employee_name', 'requests.type', 'requests.start_date', 'requests.end_date', 'requests.status', 'requests.is_seen'])
            ->orderByDesc('requests.id')
            ->paginate(10);
        } else {
            // Employee sees only their own requests
            $requests = \App\Models\Request::query()
                ->where('employee_id', $user->id)
                ->join('employees', 'requests.employee_id', '=', 'employees.id')
                ->select(['requests.id', 'employees.name as employee_name', 'requests.type', 'requests.start_date', 'requests.end_date', 'requests.status', 'requests.is_seen'])
                ->orderByDesc('requests.id')
                ->paginate(10);
        }

        // For sidebar: leave balances for employee, totals for admin
        $leaveBalances = null;
        $leaveTotals = null;
        if ($user->hasRole('admin')) {
            $leaveTotals = \App\Models\Request::where('status', 1)
                ->selectRaw('type, count(*) as total')
                ->groupBy('type')
                ->pluck('total', 'type');
        } else {
            $leaveBalances = $user->leaves()->get(['leave_type', 'balance']);
        }

        return Inertia::render('Request/Requests', [
            'requests' => $requests,
            'leaveBalances' => $leaveBalances,
            'leaveTotals' => $leaveTotals,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $user = auth()->user();
        $leaveBalances = $user->leaves()->get(['leave_type', 'balance']);
        $leaveTypes = ['Annual Leave', 'Emergency Leave', 'Sick Leave'];
        return Inertia::render('Request/RequestCreate', [
            'types' => $leaveTypes,
            'leaveBalances' => $leaveBalances,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $req = $this->validationServices->validateRequestCreationDetails($request);

        $employee = auth()->user();
        // Block if not enough balance (except sick leave)
        if ($req['type'] !== 'Sick Leave') {
            $leave = EmployeeLeave::where('employee_id', $employee->id)
                ->where('leave_type', $req['type'])->first();
            if (!$leave || $leave->balance < 1) {
                return back()->withErrors(['leave' => 'Insufficient leave balance for ' . $req['type']]);
            }
        }
        return $this->requestServices->createRequest($req, $request);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $request = \App\Models\Request::with('employee')->findOrFail($id);
        authenticateIfNotAdmin(auth()->user()->id, $request->employee_id);

        if (auth()->user()->id == $request->employee_id && $request->status != 'Pending') {
            // Mark the request as seen by the employee if it was approved or rejected.
            // This will be used to display the number of unseen requests in the sidebar of user dashboard.
            $request->update(['is_seen' => true]);
        }
        return Inertia::render('Request/RequestView', [
            'request' => $request,
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->requestServices->updateRequest($request, $id);
        $leaveRequest = \App\Models\Request::findOrFail($id);
        // If approving and not sick leave, deduct balance
        if ($request->input('status') == 1 && $leaveRequest->type !== 'Sick Leave') {
            $leave = EmployeeLeave::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type', $leaveRequest->type)->first();
            if ($leave && $leave->balance > 0) {
                $leave->balance -= 1;
                $leave->save();
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        \App\Models\Request::findOrFail($id)->delete();
        return to_route('requests.index');
    }
}
