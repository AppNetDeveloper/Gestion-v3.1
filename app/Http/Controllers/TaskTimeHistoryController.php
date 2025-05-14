<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskTimeHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TaskTimeHistoryController extends Controller
{
    /**
     * Start the timer for a specific task for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\JsonResponse
     */
    public function startTimer(Request $request, Task $task)
    {
        $user = Auth::user();

        $canLogTime = $user->can('tasks log_time');
        if (!$canLogTime && !$task->users->contains($user->id)) {
            return response()->json(['error' => __('This action is unauthorized.')], 403);
        }
        if ($user->hasRole('customer')) {
            return response()->json(['error' => __('This action is unauthorized.')], 403);
        }
        if (in_array($task->status, ['completed', 'cancelled'])) {
             return response()->json(['error' => __('Cannot start timer for a task that is already completed or cancelled.')], 400);
        }

        $activeEntry = TaskTimeHistory::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->whereNull('end_time')
            ->first();

        if ($activeEntry) {
            return response()->json(['error' => __('A timer is already active for this task.')], 409);
        }

        DB::beginTransaction();
        try {
            $timeEntry = TaskTimeHistory::create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'start_time' => now(),
                'log_date' => now()->format('Y-m-d'),
                'description' => $request->input('description'),
            ]);

            if ($task->status == 'pending') {
                $task->status = 'in_progress';
                $task->save();
            }

            DB::commit();

            return response()->json([
                'success' => __('Timer started successfully!'),
                'time_entry_id' => $timeEntry->id,
                'start_time_iso' => $timeEntry->start_time->toIso8601String(),
                'start_time_formatted' => $timeEntry->start_time->format('d/m/Y H:i:s'),
                'task_status' => $task->status
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error starting timer for task #{$task->id} by user #{$user->id}: " . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return response()->json(['error' => __('An error occurred while starting the timer.')], 500);
        }
    }

    /**
     * Stop the active timer for a specific task for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\JsonResponse
     */
    public function stopTimer(Request $request, Task $task)
    {
        $user = Auth::user();

        $activeEntry = TaskTimeHistory::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->whereNull('end_time')
            ->first();

        if (!$activeEntry) {
            return response()->json(['error' => __('No active timer found for this task.')], 404);
        }

        DB::beginTransaction();
        try {
            $endTime = Carbon::now(); // Usar una instancia de Carbon para end_time
            $activeEntry->end_time = $endTime;

            $startTime = Carbon::parse($activeEntry->start_time);

            // Asegurar que endTime sea siempre posterior o igual a startTime
            if ($endTime->lt($startTime)) {
                // Esto no debería pasar si el flujo es correcto, pero como salvaguarda:
                Log::warning("EndTime ({$endTime}) was before StartTime ({$startTime}) for TaskTimeHistory ID {$activeEntry->id}. Setting duration to 0.");
                $durationMinutes = 0;
            } else {
                // diffInMinutes devuelve la diferencia absoluta por defecto si el primer argumento es anterior al segundo.
                // Para estar seguros, podemos usar abs() o pasar true como segundo argumento.
                $durationMinutes = $startTime->diffInMinutes($endTime, true);
            }

            $activeEntry->duration_minutes = $durationMinutes;
            $activeEntry->description = $request->input('description', $activeEntry->description);
            $activeEntry->save();

            $this->updateTaskLoggedHours($task);

            DB::commit();

            return response()->json([
                'success' => __('Timer stopped successfully!'),
                'duration_minutes' => $activeEntry->duration_minutes,
                'logged_hours_task' => $task->fresh()->logged_hours,
                'time_entry' => $activeEntry->load('user:id,name') // Cargar usuario para la vista
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error stopping timer for task #{$task->id} (entry #{$activeEntry->id}): " . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return response()->json(['error' => __('An error occurred while stopping the timer.')], 500);
        }
    }

    /**
     * Show the form for editing an existing time entry.
     *
     * @param  \App\Models\TaskTimeHistory  $timeEntry
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function editEntry(TaskTimeHistory $timeEntry)
    {
        $user = Auth::user();
        if ($timeEntry->user_id !== $user->id && !$user->can('tasks update')) {
            abort(403, __('This action is unauthorized.'));
        }

        $task = $timeEntry->task->load('project');

        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'],
            ['name' => __('Projects'), 'url' => route('projects.index')],
            ['name' => $task->project->project_title, 'url' => route('projects.show', $task->project->id)],
            ['name' => $task->title, 'url' => route('tasks.show', $task->id)],
            ['name' => __('Edit Time Entry'), 'url' => route('task_time_entries.edit', $timeEntry->id)],
        ];

        return view('task_time_history.edit', compact('timeEntry', 'task', 'breadcrumbItems'));
    }

    /**
     * Update the specified time entry in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TaskTimeHistory  $timeEntry
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateEntry(Request $request, TaskTimeHistory $timeEntry)
    {
        $user = Auth::user();
        if ($timeEntry->user_id !== $user->id && !$user->can('tasks update')) {
            abort(403, __('This action is unauthorized.'));
        }

        $validator = Validator::make($request->all(), [
            'start_time' => 'required|date_format:Y-m-d\TH:i',
            'end_time' => 'required|date_format:Y-m-d\TH:i|after_or_equal:start_time',
            'log_date' => 'required|date_format:Y-m-d',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->route('task_time_entries.edit', $timeEntry->id)
                        ->withErrors($validator)
                        ->withInput()
                        ->with('error', __('Failed to update time entry. Please check the errors.'));
        }

        DB::beginTransaction();
        try {
            $startTime = Carbon::parse($request->input('start_time'));
            $endTime = Carbon::parse($request->input('end_time'));

            // Asegurar que endTime sea siempre posterior o igual a startTime
            if ($endTime->lt($startTime)) {
                 return redirect()->route('task_time_entries.edit', $timeEntry->id)
                        ->withErrors(['end_time' => __('End time cannot be before start time.')])
                        ->withInput();
            }
            $durationMinutes = $startTime->diffInMinutes($endTime, true);

            $timeEntry->update([
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_minutes' => $durationMinutes,
                'log_date' => Carbon::parse($request->input('log_date'))->format('Y-m-d'),
                'description' => $request->input('description'),
            ]);

            $this->updateTaskLoggedHours($timeEntry->task);

            DB::commit();
            return redirect()->route('tasks.show', $timeEntry->task_id)->with('success', __('Time entry updated successfully!'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating time entry #{$timeEntry->id}: " . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return redirect()->route('task_time_entries.edit', $timeEntry->id)
                        ->withInput()
                        ->with('error', __('An error occurred while updating the time entry.'));
        }
    }

    /**
     * Remove the specified time entry from storage.
     *
     * @param  \App\Models\TaskTimeHistory  $timeEntry
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroyEntry(TaskTimeHistory $timeEntry)
    {
        $user = Auth::user();
        if ($timeEntry->user_id !== $user->id && !$user->can('tasks delete')) {
             if (request()->ajax()) {
                return response()->json(['error' => __('This action is unauthorized.')], 403);
            }
            abort(403, __('This action is unauthorized.'));
        }

        $task = $timeEntry->task;

        DB::beginTransaction();
        try {
            $timeEntry->delete();
            $this->updateTaskLoggedHours($task);
            DB::commit();

            if (request()->ajax()) {
                return response()->json([
                    'success' => __('Time entry deleted successfully!'),
                    'logged_hours_task' => $task->fresh()->logged_hours // Devolver horas actualizadas
                ]);
            }
            return redirect()->route('tasks.show', $task->id)->with('success', __('Time entry deleted successfully!'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting time entry #{$timeEntry->id}: " . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            if (request()->ajax()) {
                return response()->json(['error' => __('An error occurred while deleting the time entry.')], 500);
            }
            return redirect()->route('tasks.show', $task->id)->with('error', __('An error occurred while deleting the time entry.'));
        }
    }

    /**
     * Helper function to update the total logged hours for a task.
     *
     * @param Task $task
     */
    protected function updateTaskLoggedHours(Task $task)
    {
        // Sumar todas las duraciones completadas (con end_time y duration_minutes > 0) para esta tarea
        $totalMinutes = TaskTimeHistory::where('task_id', $task->id)
            ->whereNotNull('end_time')
            ->where('duration_minutes', '>', 0) // Solo sumar duraciones positivas
            ->sum('duration_minutes');

        $task->logged_hours = $totalMinutes > 0 ? round($totalMinutes / 60, 2) : 0;
        $task->save();
        Log::info("Updated logged hours for task #{$task->id} to: {$task->logged_hours}");
    }
}
