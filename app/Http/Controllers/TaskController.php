<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Schedule;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    // List all tasks for a given schedule
    public function index(Request $request)
    {
        $scheduleId = $request->query('schedule_id');
        if (!$scheduleId) {
            return response()->json(['error' => 'schedule_id is required'], 400);
        }
        $tasks = Task::where('schedule_id', $scheduleId)->orderBy('id')->get();
        return response()->json(['tasks' => $tasks]);
    }

    // Create a new task for a schedule
    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
        ]);
        $task = Task::create($validated);
        return response()->json(['task' => $task]);
    }

    // Update a task
    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
        ]);
        $task->update($validated);
        return response()->json(['task' => $task]);
    }

    // Delete a task
    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();
        return response()->json(['success' => true]);
    }

    public function myTasksForDay(Request $request)
    {
        $user = $request->user();
        $date = $request->query('date');
        if (!$date) {
            return response()->json(['error' => 'date is required'], 400);
        }

        // Find all schedules for this user on this date
        $schedules = \App\Models\Schedule::where('employee_id', $user->id)
            ->where('day', $date)
            ->get();

        // Prepare tasks for morning and evening
        $tasks = [
            'morning' => [],
            'evening' => [],
        ];

        foreach ($schedules as $schedule) {
            $shiftType = $schedule->shift_type; // 'morning' or 'evening' (or 'night' if that's your naming)
            $taskList = \App\Models\Task::where('schedule_id', $schedule->id)->get();
            foreach ($taskList as $task) {
                if ($shiftType === 'morning') {
                    $tasks['morning'][] = $task;
                } else {
                    $tasks['evening'][] = $task;
                }
            }
        }

        return response()->json(['tasks' => $tasks]);
    }
} 