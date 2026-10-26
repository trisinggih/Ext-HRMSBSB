<?php

namespace App\Http\Controllers\Api\v1\Phase7;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\AssignTaskRequest;
use App\Http\Requests\Task\ProgressTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Http\Resources\Task\TaskResource;
use App\Models\Notification;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    // ===================== ADMIN: TASK CRUD =====================

    /** GET /admin /tasks */
    public function index(Request $request): JsonResponse
    {
        $query = Task::query()
            ->with(['creator:id,employee_id,name', 'assignees:id,employee_id,name']);

        if ($request->has('assigned_to')) {
            $query->whereHas('assignees', fn ($q) => $q->where('task_assignments.employee_id', $request->integer('assigned_to')));
        }
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->has('priority')) {
            $query->where('priority', $request->get('priority'));
        }
        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('due_date', [$request->get('from'), $request->get('to')]);
        } elseif ($request->has('from')) {
            $query->whereDate('due_date', '>=', $request->get('from'));
        } elseif ($request->has('to')) {
            $query->whereDate('due_date', '<=', $request->get('to'));
        }
        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(fn ($q) => $q->where('title', 'like', "%{$s}%")
                ->orWhere('description', 'like', "%{$s}%"));
        }

        $items = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar task',
            'data' => [
                'items' => TaskResource::collection($items)->response()->getData(true)['data'],
                'pagination' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                ],
            ],
        ]);
    }

    /** POST /admin /tasks */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['assigned_to', 'role', 'attachment']);
        $data['creator_id'] = $request->integer('assigned_to'); // creator non-null FK employees
        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('tasks', 'public');
        }

        $task = DB::transaction(function () use ($request, $data) {
            $task = Task::create($data);

            TaskAssignment::create([
                'task_id' => $task->id,
                'employee_id' => $request->integer('assigned_to'),
                'role' => $request->get('role', 'assignee'),
            ]);

            $this->notifyAssignee($task, $request->integer('assigned_to'), 'task_assigned',
                'Task Baru Ditugaskan',
                "Anda ditugaskan untuk task: {$task->title}");

            return $task;
        });

        $task->load(['creator:id,employee_id,name', 'assignees:id,employee_id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Task berhasil dibuat',
            'data' => new TaskResource($task),
        ], 201);
    }

    /** GET /admin /tasks/{task} */
    public function show(Task $task): JsonResponse
    {
        $task->load(['creator:id,employee_id,name', 'assignees:id,employee_id,name', 'comments.employee:id,employee_id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Detail task',
            'data' => new TaskResource($task),
        ]);
    }

    /** PUT /admin /tasks/{task} */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $data = $request->safe()->except(['attachment']);

        if ($request->hasFile('attachment')) {
            $new = $request->file('attachment')->store('tasks', 'public');
            if ($task->attachment) {
                Storage::disk('public')->delete($task->attachment);
            }
            $data['attachment'] = $new;
        }

        $task->update($data);
        $this->applyCompletedAt($task);

        $task->load(['creator:id,employee_id,name', 'assignees:id,employee_id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Task berhasil diperbarui',
            'data' => new TaskResource($task->fresh()),
        ]);
    }

    /** DELETE /admin /tasks/{task} */
    public function destroy(Task $task): JsonResponse
    {
        if ($task->attachment) {
            Storage::disk('public')->delete($task->attachment);
        }
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task berhasil dihapus',
            'data' => null,
        ]);
    }

    /** POST /admin /tasks/{task}/assign — pindah assignee */
    public function assign(AssignTaskRequest $request, Task $task): JsonResponse
    {
        $employeeId = $request->integer('employee_id');
        $role = $request->get('role', 'assignee');

        DB::transaction(function () use ($task, $employeeId, $role) {
            // ganti assignee: hapus assignee lama, pasang yang baru
            TaskAssignment::where('task_id', $task->id)->where('role', $role)->delete();
            TaskAssignment::updateOrCreate(
                ['task_id' => $task->id, 'employee_id' => $employeeId, 'role' => $role],
                ['task_id' => $task->id, 'employee_id' => $employeeId, 'role' => $role]
            );
        });

        $this->notifyAssignee($task, $employeeId, 'task_assigned',
            'Task Ditugaskan Ulang',
            "Task \"{$task->title}\" kini ditugaskan kepada Anda.");

        $task->load(['creator:id,employee_id,name', 'assignees:id,employee_id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Assignee task berhasil diubah',
            'data' => new TaskResource($task),
        ]);
    }

    /** POST /admin /tasks/{task}/progress — update persentase kemajuan */
    public function progress(ProgressTaskRequest $request, Task $task): JsonResponse
    {
        $progress = $request->integer('progress');
        $task->progress = $progress;
        $task->save();

        // Auto selesai kalau 100%
        $this->applyCompletedAt($task);

        if ($request->filled('note')) {
            $emp = $request->user();
            TaskComment::create([
                'task_id' => $task->id,
                'employee_id' => $task->creator_id,
                'comment' => $request->get('note'),
            ]);
        }

        $task->load(['creator:id,employee_id,name', 'assignees:id,employee_id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Progress task diperbarui',
            'data' => new TaskResource($task->fresh()),
        ]);
    }

    /** PUT /admin /tasks/{task}/status — transisi status */
    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        $to = $request->get('status');
        if (!$this->validTransition($task->status, $to)) {
            return response()->json([
                'success' => false,
                'message' => "Transisi status tidak valid dari '{$task->status}' ke '{$to}'",
                'data' => null,
            ], 422);
        }

        $task->status = $to;
        $this->applyCompletedAt($task);
        $task->save();

        // notifikasi ke semua assignee
        foreach ($task->assignees as $assignee) {
            $this->notifyAssignee($task, $assignee->id, 'task_status',
                'Status Task Berubah',
                "Status task \"{$task->title}\" menjadi \"{$to}\".");
        }

        $task->load(['creator:id,employee_id,name', 'assignees:id,employee_id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Status task berhasil diubah',
            'data' => new TaskResource($task->fresh()),
        ]);
    }

    // ===================== EMPLOYEE: data sendiri =====================

    /** GET /employee /my/tasks */
    public function myIndex(Request $request): JsonResponse
    {
        $emp = $request->user();

        $query = Task::whereHas('assignees', fn ($q) => $q->where('task_assignments.employee_id', $emp->id))
            ->with(['creator:id,employee_id,name', 'assignees:id,employee_id,name']);

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->has('priority')) {
            $query->where('priority', $request->get('priority'));
        }
        if ($request->has('date')) {
            $query->whereDate('due_date', $request->get('date'));
        }

        $items = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'My tasks',
            'data' => [
                'items' => TaskResource::collection($items)->response()->getData(true)['data'],
                'pagination' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                ],
            ],
        ]);
    }

    /** PUT /employee /my/tasks/{task}/status */
    public function myUpdateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        $emp = $request->user();
        $isMine = TaskAssignment::where('task_id', $task->id)
            ->where('employee_id', $emp->id)
            ->exists();

        if (!$isMine) {
            return response()->json([
                'success' => false,
                'message' => 'Task bukan milik Anda',
                'data' => null,
            ], 403);
        }

        $to = $request->get('status');
        if (!$this->validTransition($task->status, $to)) {
            return response()->json([
                'success' => false,
                'message' => "Transisi status tidak valid dari '{$task->status}' ke '{$to}'",
                'data' => null,
            ], 422);
        }

        $task->status = $to;
        $this->applyCompletedAt($task);
        $task->save();

        $task->load(['creator:id,employee_id,name', 'assignees:id,employee_id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Status task berhasil diubah',
            'data' => new TaskResource($task),
        ]);
    }

    /** POST /employee /my/tasks/batch */
    public function myStoreBatch(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'tasks' => 'required|array|min:1',
            'tasks.*' => 'required|string',
        ]);

        $emp = $request->user();
        $date = $request->get('date');
        $tasks = $request->get('tasks');

        DB::transaction(function () use ($emp, $date, $tasks) {
            foreach ($tasks as $taskTitle) {
                $task = Task::create([
                    'title' => $taskTitle,
                    'creator_id' => $emp->id,
                    'priority' => Task::PRIORITY_MEDIUM,
                    'status' => Task::STATUS_TODO,
                    'due_date' => $date,
                ]);

                TaskAssignment::create([
                    'task_id' => $task->id,
                    'employee_id' => $emp->id,
                    'role' => 'assignee',
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil ditambahkan',
            'data' => null,
        ]);
    }

    /** POST /employee /my/tasks/complete-all */
    public function myCompleteAll(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $emp = $request->user();
        $date = $request->get('date');

        $tasks = Task::whereDate('due_date', $date)
            ->whereHas('assignees', fn ($q) => $q->where('task_assignments.employee_id', $emp->id))
            ->where('status', '!=', Task::STATUS_DONE)
            ->get();

        foreach ($tasks as $task) {
            $task->status = Task::STATUS_DONE;
            $this->applyCompletedAt($task);
            $task->save();
        }

        return response()->json([
            'success' => true,
            'message' => count($tasks) . ' tugas berhasil diselesaikan',
            'data' => null,
        ]);
    }

    // ===================== HELPERS =====================

    private function validTransition(?string $from, string $to): bool
    {
        $from = $from ?? Task::STATUS_TODO;
        return in_array($to, Task::TRANSITIONS[$from] ?? [], true);
    }

    private function applyCompletedAt(Task $task): void
    {
        if ($task->status === Task::STATUS_DONE) {
            if ($task->progress < 100) {
                $task->progress = 100;
            }
            $task->completed_at = $task->completed_at ?? now();
        } elseif ($task->status !== Task::STATUS_DONE) {
            $task->completed_at = null;
        }
        $task->save();
    }

    private function notifyAssignee(Task $task, int $employeeId, string $type, string $title, string $body): void
    {
        Notification::create([
            'employee_id' => $employeeId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => ['task_id' => $task->id, 'task_title' => $task->title],
            'is_read' => false,
        ]);
    }
}
