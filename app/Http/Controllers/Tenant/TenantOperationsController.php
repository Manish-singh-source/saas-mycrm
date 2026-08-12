<?php

namespace App\Http\Controllers\Tenant;

use App\Services\Tenant\TenantWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TenantOperationsController extends BaseTenantController
{
    public function __construct(private readonly TenantWorkspaceService $tenant) {}

    public function projectDashboard(): JsonResponse
    {
        return $this->success(['dashboard' => [
            'cards' => [
                'active_projects' => $this->count('projects'),
                'overdue_projects' => $this->base('projects')->whereNull('completed_at')->whereDate('due_date', '<', now())->count(),
                'completed_projects' => $this->base('projects')->whereNotNull('completed_at')->count(),
                'total_budget' => (float) $this->base('projects')->sum('budget_amount'),
                'billable_minutes' => (int) $this->base('project_time_logs')->where('billable', true)->sum('minutes'),
                'project_expenses' => (float) $this->base('project_expenses')->sum('amount'),
            ],
            'by_status' => $this->grouped('projects', 'status_id'),
            'at_risk' => $this->projectRows()->whereNull('projects.completed_at')->whereDate('projects.due_date', '<=', now()->addDays(7))->limit(10)->get(),
            'overdue_milestones' => $this->base('project_milestones')->whereNull('completed_at')->whereDate('due_date', '<', now())->orderBy('due_date')->limit(10)->get(),
            'recent_time_logs' => $this->base('project_time_logs')->orderByDesc('id')->limit(10)->get(),
        ]]);
    }

    public function projects(Request $request): JsonResponse
    {
        $page = $this->filterProjectRows($request, $this->projectRows())->orderByDesc('projects.id')->paginate($request->integer('per_page', 25));

        return $this->list($page->items(), $page);
    }

    public function projectKanban(): JsonResponse
    {
        $rows = $this->projectRows()->orderBy('projects.status_id')->orderByDesc('projects.progress')->get();

        return $this->success(['kanban' => $this->kanban($rows, 'status_id')]);
    }

    public function projectGantt(): JsonResponse
    {
        $projects = $this->projectRows()->orderBy('projects.start_date')->get();
        $milestones = $this->base('project_milestones')->orderBy('due_date')->get();

        return $this->success(['gantt' => ['projects' => $projects, 'milestones' => $milestones, 'dependencies' => DB::table('task_dependencies')->where('tenant_id', $this->tenantId())->get()]]);
    }

    public function projectCalendar(): JsonResponse
    {
        return $this->success(['projects' => $this->projectRows()->whereNotNull('projects.due_date')->orderBy('projects.due_date')->get()]);
    }

    public function storeProject(Request $request): JsonResponse
    {
        $payload = $this->projectPayload($request);
        $id = DB::table('projects')->insertGetId([...$payload, 'uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'created_by' => $request->user()?->id, 'updated_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->tenant->audit($request, 'tenant_project_created', 'project', $id, null, $payload);

        return $this->success(['project' => $this->projectBundle($id)], 'Project created.', 201);
    }

    public function showProject(string $project_uuid): JsonResponse { return $this->success(['project' => $this->projectBundle($this->find('projects', $project_uuid)->id)]); }

    public function updateProject(Request $request, string $project_uuid): JsonResponse
    {
        $project = $this->find('projects', $project_uuid);
        $old = (array) $project;
        $payload = $this->projectPayload($request, true, $project->id);
        DB::table('projects')->where('id', $project->id)->update([...$payload, 'updated_by' => $request->user()?->id, 'updated_at' => now()]);
        $this->tenant->audit($request, 'tenant_project_updated', 'project', $project->id, $old, $payload);

        return $this->success(['project' => $this->projectBundle($project->id)], 'Project updated.');
    }

    public function archiveProject(Request $request, string $project_uuid): JsonResponse
    {
        $project = $this->find('projects', $project_uuid);
        DB::table('projects')->where('id', $project->id)->update(['deleted_at' => now(), 'updated_by' => $request->user()?->id, 'updated_at' => now()]);
        $this->tenant->audit($request, 'tenant_project_archived', 'project', $project->id, (array) $project, null, $request->input('reason'));

        return $this->success(null, 'Project archived.');
    }

    public function exportProjects(Request $request): JsonResponse { return $this->success(['job' => $this->tenant->createJob($request, 'export', 'projects', $request->all())], 'Project export queued.', 202); }

    public function projectChildren(Request $request, string $project_uuid, string $resource): JsonResponse
    {
        $project = $this->find('projects', $project_uuid);
        $map = ['members' => 'project_members', 'phases' => 'project_phases', 'milestones' => 'project_milestones', 'time-logs' => 'project_time_logs', 'expenses' => 'project_expenses'];
        abort_unless(isset($map[$resource]), 404);
        $rows = $this->base($map[$resource])->where('project_id', $project->id)->orderByDesc('id')->get();

        return $this->success([$this->key($resource) => $rows]);
    }

    public function storeProjectChild(Request $request, string $project_uuid, string $resource): JsonResponse
    {
        $project = $this->find('projects', $project_uuid);
        $table = ['members' => 'project_members', 'phases' => 'project_phases', 'milestones' => 'project_milestones', 'time-logs' => 'project_time_logs', 'expenses' => 'project_expenses'][$resource] ?? abort(404);
        $payload = $this->childPayload($request, $table);
        $id = DB::table($table)->insertGetId([...$payload, 'tenant_id' => $this->tenantId(), 'project_id' => $project->id]);

        return $this->success([$this->singular($resource) => DB::table($table)->where('id', $id)->first()], ucfirst($this->singular($resource)).' created.', 201);
    }

    public function updateProjectChild(Request $request, string $project_uuid, string $resource, int $id): JsonResponse
    {
        $project = $this->find('projects', $project_uuid);
        $table = ['members' => 'project_members', 'phases' => 'project_phases', 'milestones' => 'project_milestones', 'time-logs' => 'project_time_logs', 'expenses' => 'project_expenses'][$resource] ?? abort(404);
        $this->scopedChild($table, 'project_id', $project->id, $id);
        DB::table($table)->where('id', $id)->update($this->childPayload($request, $table, true));

        return $this->success([$this->singular($resource) => DB::table($table)->where('id', $id)->first()], ucfirst($this->singular($resource)).' updated.');
    }

    public function deleteProjectChild(string $project_uuid, string $resource, int $id): JsonResponse
    {
        $project = $this->find('projects', $project_uuid);
        $table = ['members' => 'project_members', 'phases' => 'project_phases', 'milestones' => 'project_milestones', 'time-logs' => 'project_time_logs', 'expenses' => 'project_expenses'][$resource] ?? abort(404);
        $this->scopedChild($table, 'project_id', $project->id, $id);
        DB::table($table)->where('id', $id)->delete();

        return $this->success(null, ucfirst($this->singular($resource)).' deleted.');
    }

    public function completeMilestone(string $project_uuid, int $milestone_id): JsonResponse
    {
        $project = $this->find('projects', $project_uuid);
        $this->scopedChild('project_milestones', 'project_id', $project->id, $milestone_id);
        DB::table('project_milestones')->where('id', $milestone_id)->update(['completed_at' => now()]);

        return $this->success(['milestone' => DB::table('project_milestones')->where('id', $milestone_id)->first()], 'Milestone completed.');
    }

    public function projectTasks(string $project_uuid): JsonResponse
    {
        $project = $this->find('projects', $project_uuid);

        return $this->success(['tasks' => $this->taskRows()->where('tasks.project_id', $project->id)->orderByDesc('tasks.id')->get()]);
    }

    public function storeProjectTask(Request $request, string $project_uuid): JsonResponse
    {
        $project = $this->find('projects', $project_uuid);
        $payload = $this->taskPayload($request);
        $id = DB::table('tasks')->insertGetId([...$payload, 'uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'project_id' => $project->id, 'created_by' => $request->user()?->id, 'updated_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);

        return $this->success(['task' => $this->taskBundle($id)], 'Task created.', 201);
    }

    public function taskDashboard(): JsonResponse
    {
        return $this->success(['dashboard' => [
            'cards' => [
                'my_open_tasks' => $this->base('tasks')->where('assigned_to', request()->user()?->id)->whereNull('completed_at')->count(),
                'overdue_tasks' => $this->base('tasks')->whereNull('completed_at')->whereDate('due_at', '<', now())->count(),
                'due_today' => $this->base('tasks')->whereDate('due_at', today())->count(),
                'completed_this_week' => $this->base('tasks')->whereDate('completed_at', '>=', now()->startOfWeek())->count(),
                'team_open_tasks' => $this->base('tasks')->whereNotNull('assigned_team_id')->whereNull('completed_at')->count(),
            ],
            'by_status' => $this->grouped('tasks', 'status_id'),
            'by_priority' => $this->grouped('tasks', 'priority_id'),
            'overdue' => $this->taskRows()->whereNull('tasks.completed_at')->whereDate('tasks.due_at', '<', now())->limit(10)->get(),
            'recently_completed' => $this->taskRows()->whereNotNull('tasks.completed_at')->orderByDesc('tasks.completed_at')->limit(10)->get(),
        ]]);
    }

    public function tasks(Request $request): JsonResponse
    {
        $query = $this->filterTaskRows($request, $this->taskRows());
        $scope = $request->route('scope') ?? $request->input('scope');
        if ($scope === 'my') $query->where('tasks.assigned_to', $request->user()?->id);
        if ($scope === 'team') $query->whereNotNull('tasks.assigned_team_id');
        $page = $query->orderByDesc('tasks.id')->paginate($request->integer('per_page', 25));

        return $this->list($page->items(), $page);
    }

    public function taskKanban(): JsonResponse { return $this->success(['kanban' => $this->kanban($this->taskRows()->orderBy('tasks.status_id')->get(), 'status_id')]); }
    public function taskCalendar(): JsonResponse { return $this->success(['tasks' => $this->taskRows()->whereNotNull('tasks.due_at')->orderBy('tasks.due_at')->get()]); }
    public function storeTask(Request $request): JsonResponse { $id = DB::table('tasks')->insertGetId([...$this->taskPayload($request), 'uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'created_by' => $request->user()?->id, 'updated_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]); return $this->success(['task' => $this->taskBundle($id)], 'Task created.', 201); }
    public function showTask(string $task_uuid): JsonResponse { return $this->success(['task' => $this->taskBundle($this->find('tasks', $task_uuid)->id)]); }
    public function updateTask(Request $request, string $task_uuid): JsonResponse { $task = $this->find('tasks', $task_uuid); DB::table('tasks')->where('id', $task->id)->update([...$this->taskPayload($request, true), 'updated_by' => $request->user()?->id, 'updated_at' => now()]); return $this->success(['task' => $this->taskBundle($task->id)], 'Task updated.'); }
    public function archiveTask(string $task_uuid): JsonResponse { $task = $this->find('tasks', $task_uuid); DB::table('tasks')->where('id', $task->id)->update(['deleted_at' => now(), 'updated_at' => now()]); return $this->success(null, 'Task archived.'); }
    public function assignTask(Request $request, string $task_uuid): JsonResponse { $task = $this->find('tasks', $task_uuid); $payload = $this->assignmentPayload($request); DB::table('tasks')->where('id', $task->id)->update([...$payload, 'assigned_by' => $request->user()?->id, 'updated_at' => now()]); DB::table('task_assignments')->insert([...$payload, 'tenant_id' => $this->tenantId(), 'task_id' => $task->id, 'assigned_by' => $request->user()?->id, 'assigned_at' => now(), 'remarks' => $request->input('remarks')]); return $this->success(['task' => $this->taskBundle($task->id)], 'Task assigned.'); }
    public function taskStatus(Request $request, string $task_uuid): JsonResponse { $task = $this->find('tasks', $task_uuid); DB::table('tasks')->where('id', $task->id)->update(['status_id' => $this->uuidToId('tenant_lookups', $request->input('status_id')), 'progress' => $request->integer('progress', (int) $task->progress), 'updated_at' => now()]); return $this->success(['task' => $this->taskBundle($task->id)], 'Task status updated.'); }
    public function completeTask(string $task_uuid): JsonResponse { $task = $this->find('tasks', $task_uuid); DB::table('tasks')->where('id', $task->id)->update(['completed_at' => now(), 'progress' => 100, 'updated_at' => now()]); return $this->success(['task' => $this->taskBundle($task->id)], 'Task completed.'); }
    public function cloneTask(Request $request, string $task_uuid): JsonResponse { $task = (array) $this->find('tasks', $task_uuid); foreach (['id','uuid','created_at','updated_at','deleted_at'] as $key) unset($task[$key]); $task['uuid'] = (string) Str::uuid(); $task['task_number'] = $request->input('task_number', $task['task_number'].'-COPY-'.Str::upper(Str::random(4))); $task['title'] .= ' Copy'; $task['created_at'] = now(); $task['updated_at'] = now(); $id = DB::table('tasks')->insertGetId($task); return $this->success(['task' => $this->taskBundle($id)], 'Task cloned.', 201); }
    public function bulkUpdateTasks(Request $request): JsonResponse { $data = $request->validate(['task_ids' => ['required', 'array'], 'updates' => ['required', 'array']]); $ids = DB::table('tasks')->where('tenant_id', $this->tenantId())->whereIn('uuid', $data['task_ids'])->pluck('id'); DB::table('tasks')->whereIn('id', $ids)->update([...$this->taskUpdatePayload($data['updates']), 'updated_at' => now()]); return $this->success(['updated' => count($ids)], 'Tasks updated.'); }
    public function exportTasks(Request $request): JsonResponse { return $this->success(['job' => $this->tenant->createJob($request, 'export', 'tasks', $request->all())], 'Task export queued.', 202); }

    public function taskChildren(string $task_uuid, string $resource): JsonResponse
    {
        $task = $this->find('tasks', $task_uuid);
        $rows = match ($resource) {
            'checklists' => $this->base('task_checklists')->where('task_id', $task->id)->get(),
            'comments' => $this->base('task_comments')->where('task_id', $task->id)->orderByDesc('id')->get(),
            'dependencies' => $this->base('task_dependencies')->where('task_id', $task->id)->get(),
            'watchers' => DB::table('task_watchers')->join('users', 'users.id', '=', 'task_watchers.user_id')->where('task_watchers.tenant_id', $this->tenantId())->where('task_watchers.task_id', $task->id)->get(['task_watchers.*', 'users.uuid as user_uuid', 'users.display_name', 'users.email']),
            'time-logs' => $this->base('task_time_logs')->where('task_id', $task->id)->orderByDesc('id')->get(),
            default => abort(404),
        };

        return $this->success([$this->key($resource) => $rows]);
    }

    public function storeTaskChild(Request $request, string $task_uuid, string $resource): JsonResponse
    {
        $task = $this->find('tasks', $task_uuid);
        $table = ['checklists' => 'task_checklists', 'comments' => 'task_comments', 'dependencies' => 'task_dependencies', 'watchers' => 'task_watchers', 'time-logs' => 'task_time_logs'][$resource] ?? abort(404);
        $payload = $this->childPayload($request, $table);
        if ($table === 'task_watchers') {
            DB::table($table)->updateOrInsert(['tenant_id' => $this->tenantId(), 'task_id' => $task->id, 'user_id' => $payload['user_id']], []);
            return $this->success(['watcher' => $payload], 'Watcher added.', 201);
        }
        $id = DB::table($table)->insertGetId([...$payload, 'tenant_id' => $this->tenantId(), 'task_id' => $task->id]);

        return $this->success([$this->singular($resource) => DB::table($table)->where('id', $id)->first()], ucfirst($this->singular($resource)).' created.', 201);
    }

    public function addChecklistItem(Request $request, string $task_uuid, int $checklist_id): JsonResponse { $task = $this->find('tasks', $task_uuid); $this->scopedChild('task_checklists', 'task_id', $task->id, $checklist_id); $id = DB::table('task_checklist_items')->insertGetId(['tenant_id' => $this->tenantId(), 'checklist_id' => $checklist_id, ...$request->validate(['title' => ['required', 'string'], 'sort_order' => ['nullable', 'integer']])]); return $this->success(['item' => DB::table('task_checklist_items')->where('id', $id)->first()], 'Checklist item created.', 201); }
    public function updateChecklistItem(Request $request, string $task_uuid, int $item_id): JsonResponse { $task = $this->find('tasks', $task_uuid); $item = $this->checklistItem($task->id, $item_id); DB::table('task_checklist_items')->where('id', $item->id)->update($request->only(['title', 'is_completed', 'sort_order'])); return $this->success(['item' => DB::table('task_checklist_items')->where('id', $item->id)->first()], 'Checklist item updated.'); }
    public function completeChecklistItem(Request $request, string $task_uuid, int $item_id): JsonResponse { $task = $this->find('tasks', $task_uuid); $item = $this->checklistItem($task->id, $item_id); DB::table('task_checklist_items')->where('id', $item->id)->update(['is_completed' => true, 'completed_by' => $request->user()?->id, 'completed_at' => now()]); return $this->success(['item' => DB::table('task_checklist_items')->where('id', $item->id)->first()], 'Checklist item completed.'); }
    public function updateTaskChild(Request $request, string $task_uuid, string $resource, int $id): JsonResponse { $task = $this->find('tasks', $task_uuid); $table = ['comments' => 'task_comments', 'time-logs' => 'task_time_logs'][$resource] ?? abort(404); $this->scopedChild($table, 'task_id', $task->id, $id); DB::table($table)->where('id', $id)->update($this->childPayload($request, $table, true)); return $this->success([$this->singular($resource) => DB::table($table)->where('id', $id)->first()], ucfirst($this->singular($resource)).' updated.'); }
    public function deleteTaskChild(string $task_uuid, string $resource, int $id): JsonResponse { $task = $this->find('tasks', $task_uuid); $table = ['comments' => 'task_comments', 'dependencies' => 'task_dependencies'][$resource] ?? abort(404); $this->scopedChild($table, 'task_id', $task->id, $id); DB::table($table)->where('id', $id)->delete(); return $this->success(null, ucfirst($this->singular($resource)).' deleted.'); }
    public function deleteWatcher(string $task_uuid, string $user_uuid): JsonResponse { $task = $this->find('tasks', $task_uuid); $userId = $this->uuidToId('users', $user_uuid); DB::table('task_watchers')->where('tenant_id', $this->tenantId())->where('task_id', $task->id)->where('user_id', $userId)->delete(); return $this->success(null, 'Watcher removed.'); }

    public function todoDashboard(): JsonResponse { return $this->success(['dashboard' => ['cards' => ['active_lists' => $this->count('todo_lists'), 'my_lists' => $this->base('todo_lists')->where('owner_user_id', request()->user()?->id)->count(), 'shared_lists' => $this->base('todo_lists')->where('visibility', '<>', 'private')->count(), 'todo_tasks' => $this->base('tasks')->where('related_type', 'todo_list')->count()], 'lists' => $this->todoRows()->limit(10)->get()]]); }
    public function todoLists(Request $request): JsonResponse { $page = $this->todoRows()->when($request->input('search'), fn ($q, $s) => $q->where('todo_lists.name', 'like', '%'.$s.'%'))->orderByDesc('todo_lists.id')->paginate($request->integer('per_page', 25)); return $this->list($page->items(), $page); }
    public function todoKanban(): JsonResponse { return $this->success(['kanban' => $this->kanban($this->todoRows()->get(), 'status')]); }
    public function todoCalendar(): JsonResponse { return $this->success(['tasks' => $this->taskRows()->where('tasks.related_type', 'todo_list')->whereNotNull('tasks.due_at')->get()]); }
    public function storeTodo(Request $request): JsonResponse { $id = DB::table('todo_lists')->insertGetId([...$this->todoPayload($request), 'uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'created_at' => now(), 'updated_at' => now()]); return $this->success(['todo_list' => $this->todoBundle($id)], 'To-do list created.', 201); }
    public function showTodo(string $todo_list_uuid): JsonResponse { return $this->success(['todo_list' => $this->todoBundle($this->find('todo_lists', $todo_list_uuid)->id)]); }
    public function updateTodo(Request $request, string $todo_list_uuid): JsonResponse { $list = $this->find('todo_lists', $todo_list_uuid); DB::table('todo_lists')->where('id', $list->id)->update([...$this->todoPayload($request, true), 'updated_at' => now()]); return $this->success(['todo_list' => $this->todoBundle($list->id)], 'To-do list updated.'); }
    public function archiveTodo(string $todo_list_uuid): JsonResponse { $list = $this->find('todo_lists', $todo_list_uuid); DB::table('todo_lists')->where('id', $list->id)->update(['deleted_at' => now(), 'updated_at' => now()]); return $this->success(null, 'To-do list archived.'); }
    public function todoTasks(string $todo_list_uuid): JsonResponse { $list = $this->find('todo_lists', $todo_list_uuid); return $this->success(['tasks' => $this->taskRows()->where('tasks.related_type', 'todo_list')->where('tasks.related_id', $list->id)->get()]); }
    public function exportTodo(Request $request): JsonResponse { return $this->success(['job' => $this->tenant->createJob($request, 'export', 'todo_lists', $request->all())], 'To-do export queued.', 202); }

    public function issueDashboard(): JsonResponse { return $this->success(['dashboard' => ['cards' => ['open_issues' => $this->count('client_issues'), 'urgent_issues' => $this->base('client_issues')->whereDate('due_at', '<=', now()->addDay())->count(), 'overdue_issues' => $this->base('client_issues')->whereNull('closed_at')->whereDate('due_at', '<', now())->count(), 'resolved_this_week' => $this->base('client_issues')->whereDate('resolved_at', '>=', now()->startOfWeek())->count()], 'by_status' => $this->grouped('client_issues', 'status_id'), 'by_priority' => $this->grouped('client_issues', 'priority_id'), 'overdue' => $this->issueRows()->whereNull('client_issues.closed_at')->whereDate('client_issues.due_at', '<', now())->limit(10)->get()]]); }
    public function issues(Request $request): JsonResponse { $page = $this->filterIssueRows($request, $this->issueRows())->orderByDesc('client_issues.id')->paginate($request->integer('per_page', 25)); return $this->list($page->items(), $page); }
    public function issueKanban(): JsonResponse { return $this->success(['kanban' => $this->kanban($this->issueRows()->get(), 'status_id')]); }
    public function storeIssue(Request $request): JsonResponse { $id = DB::table('client_issues')->insertGetId([...$this->issuePayload($request), 'uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'created_by' => $request->user()?->id, 'updated_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]); return $this->success(['issue' => $this->issueBundle($id)], 'Issue created.', 201); }
    public function showIssue(string $issue_uuid): JsonResponse { return $this->success(['issue' => $this->issueBundle($this->find('client_issues', $issue_uuid)->id)]); }
    public function updateIssue(Request $request, string $issue_uuid): JsonResponse { $issue = $this->find('client_issues', $issue_uuid); DB::table('client_issues')->where('id', $issue->id)->update([...$this->issuePayload($request, true), 'updated_by' => $request->user()?->id, 'updated_at' => now()]); return $this->success(['issue' => $this->issueBundle($issue->id)], 'Issue updated.'); }
    public function archiveIssue(string $issue_uuid): JsonResponse { $issue = $this->find('client_issues', $issue_uuid); DB::table('client_issues')->where('id', $issue->id)->update(['deleted_at' => now(), 'updated_at' => now()]); return $this->success(null, 'Issue archived.'); }
    public function assignIssue(Request $request, string $issue_uuid): JsonResponse { $issue = $this->find('client_issues', $issue_uuid); DB::table('client_issues')->where('id', $issue->id)->update([...$this->assignmentPayload($request), 'updated_at' => now()]); return $this->success(['issue' => $this->issueBundle($issue->id)], 'Issue assigned.'); }
    public function issueStatus(Request $request, string $issue_uuid): JsonResponse { $issue = $this->find('client_issues', $issue_uuid); DB::table('client_issues')->where('id', $issue->id)->update(['status_id' => $this->uuidToId('tenant_lookups', $request->input('status_id')), 'updated_at' => now()]); return $this->success(['issue' => $this->issueBundle($issue->id)], 'Issue status updated.'); }
    public function resolveIssue(Request $request, string $issue_uuid): JsonResponse { $issue = $this->find('client_issues', $issue_uuid); DB::table('client_issues')->where('id', $issue->id)->update(['resolved_at' => now(), 'updated_at' => now()]); return $this->success(['issue' => $this->issueBundle($issue->id)], 'Issue resolved.'); }
    public function closeIssue(Request $request, string $issue_uuid): JsonResponse { $issue = $this->find('client_issues', $issue_uuid); DB::table('client_issues')->where('id', $issue->id)->update(['closed_at' => now(), 'updated_at' => now()]); return $this->success(['issue' => $this->issueBundle($issue->id)], 'Issue closed.'); }
    public function reopenIssue(string $issue_uuid): JsonResponse { $issue = $this->find('client_issues', $issue_uuid); DB::table('client_issues')->where('id', $issue->id)->update(['resolved_at' => null, 'closed_at' => null, 'updated_at' => now()]); return $this->success(['issue' => $this->issueBundle($issue->id)], 'Issue reopened.'); }
    public function issueTimeLogs(string $issue_uuid): JsonResponse { $issue = $this->find('client_issues', $issue_uuid); return $this->success(['time_logs' => $this->base('task_time_logs')->where('tasks.related_type', 'client_issue')->where('tasks.related_id', $issue->id)->join('tasks', 'tasks.id', '=', 'task_time_logs.task_id')->get('task_time_logs.*')]); }
    public function storeIssueTimeLog(Request $request, string $issue_uuid): JsonResponse { $issue = $this->find('client_issues', $issue_uuid); $taskId = DB::table('tasks')->where('tenant_id', $this->tenantId())->where('related_type', 'client_issue')->where('related_id', $issue->id)->value('id') ?: DB::table('tasks')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'task_number' => 'ISS-'.$issue->issue_number, 'related_type' => 'client_issue', 'related_id' => $issue->id, 'title' => $issue->title, 'created_at' => now(), 'updated_at' => now()]); $id = DB::table('task_time_logs')->insertGetId(['tenant_id' => $this->tenantId(), 'task_id' => $taskId, ...$this->childPayload($request, 'task_time_logs')]); return $this->success(['time_log' => DB::table('task_time_logs')->where('id', $id)->first()], 'Time log created.', 201); }
    public function createIssueTask(Request $request, string $issue_uuid): JsonResponse { $issue = $this->find('client_issues', $issue_uuid); $id = DB::table('tasks')->insertGetId([...$this->taskPayload($request), 'uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'related_type' => 'client_issue', 'related_id' => $issue->id, 'created_by' => $request->user()?->id, 'updated_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]); return $this->success(['task' => $this->taskBundle($id)], 'Linked task created.', 201); }
    public function issueActivity(string $issue_uuid): JsonResponse { $issue = $this->find('client_issues', $issue_uuid); return $this->success(['activity' => $this->base('activity_logs')->where('subject_type', 'client_issue')->where('subject_id', $issue->id)->orderByDesc('id')->get()]); }
    public function exportIssues(Request $request): JsonResponse { return $this->success(['job' => $this->tenant->createJob($request, 'export', 'issues', $request->all())], 'Issue export queued.', 202); }

    public function renewalDashboard(): JsonResponse { return $this->success(['dashboard' => ['cards' => ['due_this_week' => $this->base('renewals')->whereBetween('renewal_date', [now(), now()->addWeek()])->count(), 'due_this_month' => $this->base('renewals')->whereBetween('renewal_date', [now(), now()->addMonth()])->count(), 'overdue_renewals' => $this->base('renewals')->whereDate('renewal_date', '<', now())->count(), 'auto_renew_enabled' => $this->base('renewals')->where('auto_renew', true)->count(), 'total_amount' => (float) $this->base('renewals')->sum('amount')], 'by_type' => $this->grouped('renewals', 'renewal_type'), 'by_status' => $this->grouped('renewals', 'status_id'), 'upcoming' => $this->renewalRows()->whereDate('renewals.renewal_date', '>=', now())->limit(10)->get(), 'overdue' => $this->renewalRows()->whereDate('renewals.renewal_date', '<', now())->limit(10)->get()]]); }
    public function renewals(Request $request): JsonResponse { $page = $this->filterRenewalRows($request, $this->renewalRows())->orderBy('renewals.renewal_date')->paginate($request->integer('per_page', 25)); return $this->list($page->items(), $page); }
    public function renewalCalendar(): JsonResponse { return $this->success(['renewals' => $this->renewalRows()->orderBy('renewals.renewal_date')->get()]); }
    public function storeRenewal(Request $request): JsonResponse { $id = DB::table('renewals')->insertGetId([...$this->renewalPayload($request), 'uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'created_by' => $request->user()?->id, 'updated_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]); return $this->success(['renewal' => $this->renewalBundle($id)], 'Renewal created.', 201); }
    public function showRenewal(string $renewal_uuid): JsonResponse { return $this->success(['renewal' => $this->renewalBundle($this->find('renewals', $renewal_uuid)->id)]); }
    public function updateRenewal(Request $request, string $renewal_uuid): JsonResponse { $renewal = $this->find('renewals', $renewal_uuid); DB::table('renewals')->where('id', $renewal->id)->update([...$this->renewalPayload($request, true), 'updated_by' => $request->user()?->id, 'updated_at' => now()]); return $this->success(['renewal' => $this->renewalBundle($renewal->id)], 'Renewal updated.'); }
    public function archiveRenewal(string $renewal_uuid): JsonResponse { $renewal = $this->find('renewals', $renewal_uuid); DB::table('renewals')->where('id', $renewal->id)->update(['deleted_at' => now(), 'updated_at' => now()]); return $this->success(null, 'Renewal archived.'); }
    public function renewRenewal(Request $request, string $renewal_uuid): JsonResponse { $renewal = $this->find('renewals', $renewal_uuid); $data = $request->validate(['new_end_date' => ['required', 'date'], 'renewal_date' => ['nullable', 'date'], 'remarks' => ['nullable', 'string']]); DB::table('renewal_history')->insert(['tenant_id' => $this->tenantId(), 'renewal_id' => $renewal->id, 'old_end_date' => $renewal->end_date, 'new_end_date' => $data['new_end_date'], 'status_id' => $renewal->status_id, 'remarks' => $data['remarks'] ?? null, 'created_by' => $request->user()?->id, 'created_at' => now()]); DB::table('renewals')->where('id', $renewal->id)->update(['end_date' => $data['new_end_date'], 'renewal_date' => $data['renewal_date'] ?? $data['new_end_date'], 'updated_at' => now()]); return $this->success(['renewal' => $this->renewalBundle($renewal->id)], 'Renewal extended.'); }
    public function cancelRenewal(Request $request, string $renewal_uuid): JsonResponse { $renewal = $this->find('renewals', $renewal_uuid); DB::table('renewal_history')->insert(['tenant_id' => $this->tenantId(), 'renewal_id' => $renewal->id, 'old_end_date' => $renewal->end_date, 'new_end_date' => $renewal->end_date, 'status_id' => $renewal->status_id, 'remarks' => $request->input('reason', 'Cancelled'), 'created_by' => $request->user()?->id, 'created_at' => now()]); DB::table('renewals')->where('id', $renewal->id)->update(['auto_renew' => false, 'updated_at' => now()]); return $this->success(['renewal' => $this->renewalBundle($renewal->id)], 'Renewal cancelled.'); }
    public function renewalChildren(string $renewal_uuid, string $resource): JsonResponse { $renewal = $this->find('renewals', $renewal_uuid); $table = ['items' => 'renewal_items', 'history' => 'renewal_history', 'reminders' => 'renewal_reminders'][$resource] ?? abort(404); return $this->success([$this->key($resource) => $this->base($table)->where('renewal_id', $renewal->id)->orderByDesc('id')->get()]); }
    public function storeRenewalChild(Request $request, string $renewal_uuid, string $resource): JsonResponse { $renewal = $this->find('renewals', $renewal_uuid); $table = ['items' => 'renewal_items', 'reminders' => 'renewal_reminders'][$resource] ?? abort(404); $id = DB::table($table)->insertGetId([...$this->childPayload($request, $table), 'tenant_id' => $this->tenantId(), 'renewal_id' => $renewal->id]); return $this->success([$this->singular($resource) => DB::table($table)->where('id', $id)->first()], ucfirst($this->singular($resource)).' created.', 201); }
    public function updateRenewalChild(Request $request, string $renewal_uuid, string $resource, int $id): JsonResponse { $renewal = $this->find('renewals', $renewal_uuid); $table = ['items' => 'renewal_items', 'reminders' => 'renewal_reminders'][$resource] ?? abort(404); $this->scopedChild($table, 'renewal_id', $renewal->id, $id); DB::table($table)->where('id', $id)->update($this->childPayload($request, $table, true)); return $this->success([$this->singular($resource) => DB::table($table)->where('id', $id)->first()], ucfirst($this->singular($resource)).' updated.'); }
    public function deleteRenewalChild(string $renewal_uuid, string $resource, int $id): JsonResponse { $renewal = $this->find('renewals', $renewal_uuid); $table = ['items' => 'renewal_items'][$resource] ?? abort(404); $this->scopedChild($table, 'renewal_id', $renewal->id, $id); DB::table($table)->where('id', $id)->delete(); return $this->success(null, ucfirst($this->singular($resource)).' deleted.'); }
    public function sendRenewalReminder(Request $request, string $renewal_uuid): JsonResponse { $renewal = $this->find('renewals', $renewal_uuid); $id = DB::table('renewal_reminders')->insertGetId(['tenant_id' => $this->tenantId(), 'renewal_id' => $renewal->id, 'remind_at' => now(), 'channel' => $request->input('channel', 'email'), 'sent_at' => now(), 'status' => 'sent']); return $this->success(['reminder' => DB::table('renewal_reminders')->where('id', $id)->first()], 'Reminder sent.'); }
    public function exportRenewals(Request $request): JsonResponse { return $this->success(['job' => $this->tenant->createJob($request, 'export', 'renewals', $request->all())], 'Renewal export queued.', 202); }

    public function calendars(Request $request): JsonResponse { $page = $this->calendarRows()->when($request->input('search'), fn ($q, $s) => $q->where('calendars.name', 'like', '%'.$s.'%'))->orderBy('calendars.name')->paginate($request->integer('per_page', 25)); return $this->list($page->items(), $page); }
    public function storeCalendar(Request $request): JsonResponse { $id = DB::table('calendars')->insertGetId([...$this->calendarPayload($request), 'uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'created_at' => now(), 'updated_at' => now()]); return $this->success(['calendar' => DB::table('calendars')->where('id', $id)->first()], 'Calendar created.', 201); }
    public function showCalendar(string $calendar_uuid): JsonResponse { $calendar = $this->find('calendars', $calendar_uuid); return $this->success(['calendar' => ['calendar' => $calendar, 'events' => $this->eventRows()->where('calendar_events.calendar_id', $calendar->id)->get(), 'sync_logs' => $this->base('calendar_sync_logs')->where('calendar_id', $calendar->id)->get()]]); }
    public function updateCalendar(Request $request, string $calendar_uuid): JsonResponse { $calendar = $this->find('calendars', $calendar_uuid); DB::table('calendars')->where('id', $calendar->id)->update([...$this->calendarPayload($request, true), 'updated_at' => now()]); return $this->success(['calendar' => DB::table('calendars')->where('id', $calendar->id)->first()], 'Calendar updated.'); }
    public function deleteCalendar(string $calendar_uuid): JsonResponse { $calendar = $this->find('calendars', $calendar_uuid); DB::table('calendars')->where('id', $calendar->id)->delete(); return $this->success(null, 'Calendar deleted.'); }
    public function events(Request $request): JsonResponse { $page = $this->filterEventRows($request, $this->eventRows())->orderBy('calendar_events.starts_at')->paginate($request->integer('per_page', 25)); return $this->list($page->items(), $page); }
    public function storeEvent(Request $request): JsonResponse { $id = DB::table('calendar_events')->insertGetId([...$this->eventPayload($request), 'uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'created_by' => $request->user()?->id, 'updated_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]); $this->replaceEventChildren($request, $id); return $this->success(['event' => $this->eventBundle($id)], 'Event created.', 201); }
    public function showEvent(string $event_uuid): JsonResponse { return $this->success(['event' => $this->eventBundle($this->find('calendar_events', $event_uuid)->id)]); }
    public function updateEvent(Request $request, string $event_uuid): JsonResponse { $event = $this->find('calendar_events', $event_uuid); DB::table('calendar_events')->where('id', $event->id)->update([...$this->eventPayload($request, true), 'updated_by' => $request->user()?->id, 'updated_at' => now()]); $this->replaceEventChildren($request, $event->id, true); return $this->success(['event' => $this->eventBundle($event->id)], 'Event updated.'); }
    public function deleteEvent(string $event_uuid): JsonResponse { $event = $this->find('calendar_events', $event_uuid); DB::table('calendar_events')->where('id', $event->id)->update(['deleted_at' => now(), 'updated_at' => now()]); return $this->success(null, 'Event cancelled.'); }
    public function rescheduleEvent(Request $request, string $event_uuid): JsonResponse { $event = $this->find('calendar_events', $event_uuid); $data = $request->validate(['starts_at' => ['required', 'date'], 'ends_at' => ['nullable', 'date'], 'reason' => ['nullable', 'string']]); DB::table('calendar_events')->where('id', $event->id)->update([...$data, 'updated_at' => now()]); return $this->success(['event' => $this->eventBundle($event->id)], 'Event rescheduled.'); }
    public function eventChildren(string $event_uuid, string $resource): JsonResponse { $event = $this->find('calendar_events', $event_uuid); $table = ['attendees' => 'calendar_event_attendees', 'reminders' => 'calendar_event_reminders'][$resource] ?? abort(404); return $this->success([$this->key($resource) => $this->base($table)->where('event_id', $event->id)->get()]); }
    public function storeEventChild(Request $request, string $event_uuid, string $resource): JsonResponse { $event = $this->find('calendar_events', $event_uuid); $table = ['attendees' => 'calendar_event_attendees', 'reminders' => 'calendar_event_reminders'][$resource] ?? abort(404); $id = DB::table($table)->insertGetId([...$this->childPayload($request, $table), 'tenant_id' => $this->tenantId(), 'event_id' => $event->id]); return $this->success([$this->singular($resource) => DB::table($table)->where('id', $id)->first()], ucfirst($this->singular($resource)).' created.', 201); }
    public function updateEventChild(Request $request, string $event_uuid, string $resource, int $id): JsonResponse { $event = $this->find('calendar_events', $event_uuid); $table = ['attendees' => 'calendar_event_attendees'][$resource] ?? abort(404); $this->scopedChild($table, 'event_id', $event->id, $id); DB::table($table)->where('id', $id)->update($this->childPayload($request, $table, true)); return $this->success([$this->singular($resource) => DB::table($table)->where('id', $id)->first()], ucfirst($this->singular($resource)).' updated.'); }
    public function videoMeeting(Request $request, string $event_uuid): JsonResponse { $event = $this->find('calendar_events', $event_uuid); DB::table('video_meetings')->updateOrInsert(['tenant_id' => $this->tenantId(), 'event_id' => $event->id], ['provider' => $request->input('provider', 'manual'), 'meeting_id' => $request->input('meeting_id'), 'meeting_url' => $request->input('meeting_url'), 'passcode' => $request->input('passcode')]); return $this->success(['video_meeting' => DB::table('video_meetings')->where('tenant_id', $this->tenantId())->where('event_id', $event->id)->first()], 'Video meeting saved.'); }
    public function roomBooking(Request $request, string $event_uuid): JsonResponse { $event = $this->find('calendar_events', $event_uuid); $data = $request->validate(['room_id' => ['required'], 'status' => ['nullable', 'string']]); $roomId = is_numeric($data['room_id']) ? (int) $data['room_id'] : $this->uuidToId('meeting_rooms', $data['room_id']); $conflict = DB::table('meeting_room_bookings')->join('calendar_events', 'calendar_events.id', '=', 'meeting_room_bookings.event_id')->where('meeting_room_bookings.tenant_id', $this->tenantId())->where('room_id', $roomId)->where('event_id', '<>', $event->id)->where('calendar_events.starts_at', '<', $event->ends_at)->where('calendar_events.ends_at', '>', $event->starts_at)->exists(); abort_if($conflict, 409, 'Room booking conflict.'); $id = DB::table('meeting_room_bookings')->insertGetId(['tenant_id' => $this->tenantId(), 'room_id' => $roomId, 'event_id' => $event->id, 'booked_by' => $request->user()?->id, 'status' => $data['status'] ?? 'booked']); return $this->success(['booking' => DB::table('meeting_room_bookings')->where('id', $id)->first()], 'Room booked.', 201); }
    public function meetingRooms(): JsonResponse { return $this->success(['meeting_rooms' => $this->base('meeting_rooms')->orderBy('name')->get()]); }
    public function storeMeetingRoom(Request $request): JsonResponse { $id = DB::table('meeting_rooms')->insertGetId(['tenant_id' => $this->tenantId(), ...$request->validate(['name' => ['required', 'string'], 'office_id' => ['nullable'], 'location' => ['nullable', 'string'], 'capacity' => ['nullable', 'integer'], 'status' => ['nullable', 'string']])]); return $this->success(['meeting_room' => DB::table('meeting_rooms')->where('id', $id)->first()], 'Room created.', 201); }
    public function updateMeetingRoom(Request $request, int $room_id): JsonResponse { DB::table('meeting_rooms')->where('tenant_id', $this->tenantId())->where('id', $room_id)->update($request->only(['name', 'office_id', 'location', 'capacity', 'status'])); return $this->success(['meeting_room' => DB::table('meeting_rooms')->where('id', $room_id)->first()], 'Room updated.'); }

    private function tenantId(): int { return app(\App\Tenancy\TenantContext::class)->id(); }
    private function base(string $table) { $query = DB::table($table)->where($table.'.tenant_id', $this->tenantId()); if (in_array($table, ['projects','tasks','todo_lists','client_issues','renewals','calendar_events'], true)) $query->whereNull($table.'.deleted_at'); return $query; }
    private function count(string $table): int { return (int) $this->base($table)->count(); }
    private function find(string $table, string $uuid): object { return $this->base($table)->where($table.'.uuid', $uuid)->first() ?: abort(404, 'Resource not found.'); }
    private function uuidToId(string $table, mixed $uuid): ?int { if ($uuid === null || $uuid === '') return null; if (is_numeric($uuid)) return (int) $uuid; return DB::table($table)->where('uuid', $uuid)->when(in_array($table, ['users','teams','parties','projects','tasks','todo_lists','client_issues','renewals','calendars','meeting_rooms'], true), fn ($q) => $q->where('tenant_id', $this->tenantId()))->when($table === 'tenant_lookups', fn ($q) => $q->where(fn ($inner) => $inner->where('tenant_id', $this->tenantId())->orWhereNull('tenant_id')))->value('id') ?: abort(404, 'Referenced resource not found.'); }
    private function scopedChild(string $table, string $parentColumn, int $parentId, int $id): object { return DB::table($table)->where('tenant_id', $this->tenantId())->where($parentColumn, $parentId)->where('id', $id)->first() ?: abort(404, 'Child resource not found.'); }

    private function projectRows() { return $this->base('projects')->leftJoin('parties as clients', 'clients.id', '=', 'projects.client_party_id')->leftJoin('users as managers', 'managers.id', '=', 'projects.project_manager_id')->select('projects.*', 'clients.uuid as client_uuid', 'clients.display_name as client_name', 'managers.uuid as manager_uuid', 'managers.display_name as manager_name'); }
    private function taskRows() { return $this->base('tasks')->leftJoin('projects', 'projects.id', '=', 'tasks.project_id')->leftJoin('users as assignees', 'assignees.id', '=', 'tasks.assigned_to')->leftJoin('teams', 'teams.id', '=', 'tasks.assigned_team_id')->select('tasks.*', 'projects.uuid as project_uuid', 'projects.name as project_name', 'assignees.uuid as assignee_uuid', 'assignees.display_name as assignee_name', 'teams.uuid as team_uuid', 'teams.name as team_name'); }
    private function todoRows() { return $this->base('todo_lists')->leftJoin('users as owners', 'owners.id', '=', 'todo_lists.owner_user_id')->leftJoin('teams', 'teams.id', '=', 'todo_lists.team_id')->select('todo_lists.*', 'owners.uuid as owner_uuid', 'owners.display_name as owner_name', 'teams.uuid as team_uuid', 'teams.name as team_name'); }
    private function issueRows() { return $this->base('client_issues')->join('parties as clients', 'clients.id', '=', 'client_issues.client_party_id')->leftJoin('users as assignees', 'assignees.id', '=', 'client_issues.assigned_to')->leftJoin('teams', 'teams.id', '=', 'client_issues.assigned_team_id')->select('client_issues.*', 'clients.uuid as client_uuid', 'clients.display_name as client_name', 'assignees.uuid as assignee_uuid', 'assignees.display_name as assignee_name', 'teams.uuid as team_uuid', 'teams.name as team_name'); }
    private function renewalRows() { return $this->base('renewals')->leftJoin('parties', 'parties.id', '=', 'renewals.party_id')->leftJoin('users as owners', 'owners.id', '=', 'renewals.owner_user_id')->select('renewals.*', 'parties.uuid as party_uuid', 'parties.display_name as party_name', 'owners.uuid as owner_uuid', 'owners.display_name as owner_name'); }
    private function calendarRows() { return $this->base('calendars')->leftJoin('users as owners', 'owners.id', '=', 'calendars.owner_user_id')->leftJoin('teams', 'teams.id', '=', 'calendars.team_id')->select('calendars.*', 'owners.uuid as owner_uuid', 'owners.display_name as owner_name', 'teams.uuid as team_uuid', 'teams.name as team_name'); }
    private function eventRows() { return $this->base('calendar_events')->join('calendars', 'calendars.id', '=', 'calendar_events.calendar_id')->select('calendar_events.*', 'calendars.uuid as calendar_uuid', 'calendars.name as calendar_name'); }

    private function projectBundle(int $id): array { $project = DB::table('projects')->where('id', $id)->first(); return ['project' => $project, 'members' => $this->base('project_members')->where('project_id', $id)->get(), 'phases' => $this->base('project_phases')->where('project_id', $id)->get(), 'milestones' => $this->base('project_milestones')->where('project_id', $id)->get(), 'tasks' => $this->taskRows()->where('tasks.project_id', $id)->get(), 'time_logs' => $this->base('project_time_logs')->where('project_id', $id)->get(), 'expenses' => $this->base('project_expenses')->where('project_id', $id)->get(), 'activity' => $this->base('activity_logs')->where('subject_type', 'project')->where('subject_id', $id)->get()]; }
    private function taskBundle(int $id): array { return ['task' => DB::table('tasks')->where('id', $id)->first(), 'checklists' => $this->base('task_checklists')->where('task_id', $id)->get(), 'comments' => $this->base('task_comments')->where('task_id', $id)->get(), 'dependencies' => $this->base('task_dependencies')->where('task_id', $id)->get(), 'watchers' => DB::table('task_watchers')->join('users', 'users.id', '=', 'task_watchers.user_id')->where('task_watchers.tenant_id', $this->tenantId())->where('task_watchers.task_id', $id)->get(['task_watchers.*', 'users.uuid as user_uuid', 'users.display_name', 'users.email']), 'time_logs' => $this->base('task_time_logs')->where('task_id', $id)->get(), 'activity' => $this->base('activity_logs')->where('subject_type', 'task')->where('subject_id', $id)->get()]; }
    private function todoBundle(int $id): array { return ['todo_list' => DB::table('todo_lists')->where('id', $id)->first(), 'tasks' => $this->taskRows()->where('tasks.related_type', 'todo_list')->where('tasks.related_id', $id)->get()]; }
    private function issueBundle(int $id): array { return ['issue' => DB::table('client_issues')->where('id', $id)->first(), 'linked_tasks' => $this->taskRows()->where('tasks.related_type', 'client_issue')->where('tasks.related_id', $id)->get(), 'time_logs' => DB::table('task_time_logs')->join('tasks', 'tasks.id', '=', 'task_time_logs.task_id')->where('task_time_logs.tenant_id', $this->tenantId())->where('tasks.related_type', 'client_issue')->where('tasks.related_id', $id)->get('task_time_logs.*'), 'activity' => $this->base('activity_logs')->where('subject_type', 'client_issue')->where('subject_id', $id)->get()]; }
    private function renewalBundle(int $id): array { return ['renewal' => DB::table('renewals')->where('id', $id)->first(), 'items' => $this->base('renewal_items')->where('renewal_id', $id)->get(), 'history' => $this->base('renewal_history')->where('renewal_id', $id)->get(), 'reminders' => $this->base('renewal_reminders')->where('renewal_id', $id)->get()]; }
    private function eventBundle(int $id): array { return ['event' => DB::table('calendar_events')->where('id', $id)->first(), 'attendees' => $this->base('calendar_event_attendees')->where('event_id', $id)->get(), 'reminders' => $this->base('calendar_event_reminders')->where('event_id', $id)->get(), 'room_bookings' => $this->base('meeting_room_bookings')->where('event_id', $id)->get(), 'video_meeting' => $this->base('video_meetings')->where('event_id', $id)->first()]; }

    private function projectPayload(Request $request, bool $partial = false, ?int $ignoreId = null): array { $data = $request->validate(['project_number' => [$partial ? 'sometimes' : 'required', 'string'], 'name' => [$partial ? 'sometimes' : 'required', 'string'], 'description' => ['nullable', 'string'], 'client_party_id' => ['nullable'], 'project_manager_id' => ['nullable'], 'category_id' => ['nullable'], 'type_id' => ['nullable'], 'status_id' => ['nullable'], 'priority_id' => ['nullable'], 'start_date' => ['nullable', 'date'], 'due_date' => ['nullable', 'date'], 'completed_at' => ['nullable', 'date'], 'budget_amount' => ['nullable'], 'billing_type' => ['nullable', 'string'], 'progress' => ['nullable', 'integer']]); $this->ensureTenantUnique('projects', 'project_number', $data['project_number'] ?? null, $ignoreId, 'Project number already exists for this tenant.'); return $this->mapIds($data, ['client_party_id' => 'parties', 'project_manager_id' => 'users', 'category_id' => 'tenant_lookups', 'type_id' => 'tenant_lookups', 'status_id' => 'tenant_lookups', 'priority_id' => 'tenant_lookups']); }
    private function taskPayload(Request $request, bool $partial = false): array { return $this->taskUpdatePayload($request->validate(['task_number' => [$partial ? 'sometimes' : 'required', 'string'], 'parent_task_id' => ['nullable'], 'project_id' => ['nullable'], 'related_type' => ['nullable', 'string'], 'related_id' => ['nullable'], 'title' => [$partial ? 'sometimes' : 'required', 'string'], 'description' => ['nullable', 'string'], 'status_id' => ['nullable'], 'priority_id' => ['nullable'], 'category_id' => ['nullable'], 'assigned_to' => ['nullable'], 'assigned_team_id' => ['nullable'], 'start_at' => ['nullable', 'date'], 'due_at' => ['nullable', 'date'], 'completed_at' => ['nullable', 'date'], 'estimated_minutes' => ['nullable', 'integer'], 'actual_minutes' => ['nullable', 'integer'], 'progress' => ['nullable', 'integer'], 'is_recurring' => ['nullable', 'boolean'], 'recurrence_rule' => ['nullable']])); }
    private function taskUpdatePayload(array $data): array { if (isset($data['recurrence_rule']) && is_array($data['recurrence_rule'])) $data['recurrence_rule'] = json_encode($data['recurrence_rule']); return $this->mapIds($data, ['parent_task_id' => 'tasks', 'project_id' => 'projects', 'status_id' => 'tenant_lookups', 'priority_id' => 'tenant_lookups', 'category_id' => 'tenant_lookups', 'assigned_to' => 'users', 'assigned_team_id' => 'teams']); }
    private function todoPayload(Request $request, bool $partial = false): array { $data = $request->validate(['name' => [$partial ? 'sometimes' : 'required', 'string'], 'description' => ['nullable', 'string'], 'owner_user_id' => ['nullable'], 'team_id' => ['nullable'], 'visibility' => ['nullable', 'string'], 'color' => ['nullable', 'string'], 'icon' => ['nullable', 'string'], 'is_default' => ['nullable', 'boolean'], 'status' => ['nullable', 'string']]); return $this->mapIds($data, ['owner_user_id' => 'users', 'team_id' => 'teams']); }
    private function issuePayload(Request $request, bool $partial = false): array { $data = $request->validate(['issue_number' => [$partial ? 'sometimes' : 'required', 'string'], 'client_party_id' => [$partial ? 'sometimes' : 'required'], 'contact_id' => ['nullable'], 'project_id' => ['nullable'], 'title' => [$partial ? 'sometimes' : 'required', 'string'], 'description' => ['nullable', 'string'], 'type_id' => ['nullable'], 'category_id' => ['nullable'], 'status_id' => ['nullable'], 'priority_id' => ['nullable'], 'assigned_to' => ['nullable'], 'assigned_team_id' => ['nullable'], 'due_at' => ['nullable', 'date'], 'resolved_at' => ['nullable', 'date'], 'closed_at' => ['nullable', 'date']]); return $this->mapIds($data, ['client_party_id' => 'parties', 'contact_id' => 'party_contacts', 'project_id' => 'projects', 'type_id' => 'tenant_lookups', 'category_id' => 'tenant_lookups', 'status_id' => 'tenant_lookups', 'priority_id' => 'tenant_lookups', 'assigned_to' => 'users', 'assigned_team_id' => 'teams']); }
    private function renewalPayload(Request $request, bool $partial = false): array { $data = $request->validate(['renewal_number' => [$partial ? 'sometimes' : 'required', 'string'], 'party_id' => ['nullable'], 'renewal_type' => [$partial ? 'sometimes' : 'required', 'string'], 'title' => [$partial ? 'sometimes' : 'required', 'string'], 'description' => ['nullable', 'string'], 'start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date'], 'renewal_date' => [$partial ? 'sometimes' : 'required', 'date'], 'amount' => ['nullable'], 'currency' => ['nullable', 'string'], 'reminder_days_before' => ['nullable', 'integer'], 'auto_renew' => ['nullable', 'boolean'], 'status_id' => ['nullable'], 'owner_user_id' => ['nullable']]); return $this->mapIds($data, ['party_id' => 'parties', 'status_id' => 'tenant_lookups', 'owner_user_id' => 'users']); }
    private function calendarPayload(Request $request, bool $partial = false): array { $data = $request->validate(['owner_user_id' => ['nullable'], 'team_id' => ['nullable'], 'name' => [$partial ? 'sometimes' : 'required', 'string'], 'calendar_type' => [$partial ? 'sometimes' : 'required', 'string'], 'color' => ['nullable', 'string'], 'timezone' => ['nullable', 'string'], 'visibility' => ['nullable', 'string'], 'status' => ['nullable', 'string']]); return $this->mapIds($data, ['owner_user_id' => 'users', 'team_id' => 'teams']); }
    private function eventPayload(Request $request, bool $partial = false): array { $data = $request->validate(['calendar_id' => [$partial ? 'sometimes' : 'required'], 'related_type' => ['nullable', 'string'], 'related_id' => ['nullable'], 'title' => [$partial ? 'sometimes' : 'required', 'string'], 'description' => ['nullable', 'string'], 'location' => ['nullable', 'string'], 'starts_at' => [$partial ? 'sometimes' : 'required', 'date'], 'ends_at' => ['nullable', 'date'], 'timezone' => ['nullable', 'string'], 'all_day' => ['nullable', 'boolean'], 'recurrence_rule' => ['nullable'], 'status' => ['nullable', 'string']]); if (isset($data['recurrence_rule']) && is_array($data['recurrence_rule'])) $data['recurrence_rule'] = json_encode($data['recurrence_rule']); return $this->mapIds($data, ['calendar_id' => 'calendars']); }
    private function assignmentPayload(Request $request): array { return $this->mapIds($request->validate(['assigned_to' => ['nullable'], 'assigned_team_id' => ['nullable']]), ['assigned_to' => 'users', 'assigned_team_id' => 'teams']); }
    private function childPayload(Request $request, string $table, bool $partial = false): array { $fields = ['project_members' => ['user_id','team_id','role_id','billing_rate','allocation_percent','joined_at','left_at'], 'project_phases' => ['name','start_date','due_date','status_id','sort_order'], 'project_milestones' => ['phase_id','name','due_date','completed_at','status_id'], 'project_time_logs' => ['task_id','user_id','started_at','ended_at','minutes','billable'], 'project_expenses' => ['vendor_party_id','amount','currency','expense_date','status_id'], 'task_checklists' => ['title','sort_order'], 'task_comments' => ['parent_id','user_id','comment','created_at'], 'task_dependencies' => ['depends_on_task_id','dependency_type'], 'task_watchers' => ['user_id'], 'task_time_logs' => ['user_id','started_at','ended_at','minutes','notes'], 'renewal_items' => ['name','quantity','unit_price','amount'], 'renewal_reminders' => ['remind_at','channel','sent_at','status'], 'calendar_event_attendees' => ['attendee_type','user_id','team_id','contact_id','email','response_status'], 'calendar_event_reminders' => ['channel','remind_at','sent_at','status']][$table] ?? []; $data = $request->only($fields); if (!$partial) $data = array_filter($data, fn ($value) => $value !== null && $value !== ''); return $this->mapIds($data, ['user_id' => 'users', 'team_id' => 'teams', 'role_id' => 'tenant_lookups', 'status_id' => 'tenant_lookups', 'phase_id' => 'project_phases', 'task_id' => 'tasks', 'vendor_party_id' => 'parties', 'depends_on_task_id' => 'tasks', 'parent_id' => 'task_comments', 'contact_id' => 'party_contacts']); }
    private function mapIds(array $data, array $map): array { foreach ($map as $key => $table) if (array_key_exists($key, $data)) $data[$key] = $this->uuidToId($table, $data[$key]); return $data; }
    private function ensureTenantUnique(string $table, string $column, mixed $value, ?int $ignoreId, string $message): void { if ($value === null || $value === '') return; $exists = DB::table($table)->where('tenant_id', $this->tenantId())->where($column, $value)->when($ignoreId, fn ($q) => $q->where('id', '<>', $ignoreId))->exists(); if ($exists) throw ValidationException::withMessages([$column => [$message]]); }
    private function filterProjectRows(Request $request, $query) { if ($s = $request->input('search')) $query->where(fn ($q) => $q->where('projects.name', 'like', '%'.$s.'%')->orWhere('projects.project_number', 'like', '%'.$s.'%')); return $query; }
    private function filterTaskRows(Request $request, $query) { if ($s = $request->input('search')) $query->where(fn ($q) => $q->where('tasks.title', 'like', '%'.$s.'%')->orWhere('tasks.task_number', 'like', '%'.$s.'%')); return $query; }
    private function filterIssueRows(Request $request, $query) { if ($s = $request->input('search')) $query->where(fn ($q) => $q->where('client_issues.title', 'like', '%'.$s.'%')->orWhere('client_issues.issue_number', 'like', '%'.$s.'%')); return $query; }
    private function filterRenewalRows(Request $request, $query) { if ($type = ($request->route('renewal_type') ?? $request->input('renewal_type'))) $query->where('renewals.renewal_type', $type); if ($s = $request->input('search')) $query->where(fn ($q) => $q->where('renewals.title', 'like', '%'.$s.'%')->orWhere('renewals.renewal_number', 'like', '%'.$s.'%')); return $query; }
    private function filterEventRows(Request $request, $query) { if ($view = $request->input('view')) { if ($view === 'daily') $query->whereDate('calendar_events.starts_at', $request->input('date', today())); if ($view === 'weekly') $query->whereBetween('calendar_events.starts_at', [now()->startOfWeek(), now()->endOfWeek()]); if ($view === 'monthly') $query->whereBetween('calendar_events.starts_at', [now()->startOfMonth(), now()->endOfMonth()]); if ($view === 'my') $query->where('calendar_events.created_by', $request->user()?->id); } if ($s = $request->input('search')) $query->where('calendar_events.title', 'like', '%'.$s.'%'); return $query; }
    private function grouped(string $table, string $column): array { return $this->base($table)->selectRaw($column.' as label, count(*) as total')->groupBy($column)->get()->all(); }
    private function kanban($rows, string $field): array { return $rows->groupBy(fn ($row) => (string) ($row->{$field} ?? 'unassigned'))->map(fn ($items) => ['total' => $items->count(), 'rows' => $items->values()->all()])->all(); }
    private function replaceEventChildren(Request $request, int $eventId, bool $replace = false): void { if ($replace && $request->has('attendees')) DB::table('calendar_event_attendees')->where('tenant_id', $this->tenantId())->where('event_id', $eventId)->delete(); foreach ($request->input('attendees', []) as $attendee) DB::table('calendar_event_attendees')->insert(['tenant_id' => $this->tenantId(), 'event_id' => $eventId, ...$this->mapIds($attendee, ['user_id' => 'users', 'team_id' => 'teams', 'contact_id' => 'party_contacts'])]); if ($replace && $request->has('reminders')) DB::table('calendar_event_reminders')->where('tenant_id', $this->tenantId())->where('event_id', $eventId)->delete(); foreach ($request->input('reminders', []) as $reminder) DB::table('calendar_event_reminders')->insert(['tenant_id' => $this->tenantId(), 'event_id' => $eventId, ...$reminder]); }
    private function checklistItem(int $taskId, int $itemId): object { return DB::table('task_checklist_items')->join('task_checklists', 'task_checklists.id', '=', 'task_checklist_items.checklist_id')->where('task_checklist_items.tenant_id', $this->tenantId())->where('task_checklists.task_id', $taskId)->where('task_checklist_items.id', $itemId)->first('task_checklist_items.*') ?: abort(404, 'Checklist item not found.'); }
    private function key(string $resource): string { return str_replace('-', '_', $resource); }
    private function singular(string $resource): string { return rtrim($this->key($resource), 's'); }
}
