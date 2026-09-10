<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Shared\BaseApiController;
use App\Http\Requests\TenantAttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\TenantLookup;
use App\Models\Staff;
use App\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TenantHrmsController extends BaseApiController
{
    public function __construct(private readonly TenantContext $tenant) {}
    private function tid(): int { return $this->tenant->id(); }
    private function staff(mixed $value): Staff
    {
        $q = Staff::where('tenant_id', $this->tid());
        return is_numeric($value) ? $q->whereKey((int) $value)->firstOrFail() : $q->where('uuid', (string) $value)->firstOrFail();
    }
    private function currentStaff(): Staff
    {
        $user = request()->user();
        return Staff::where('tenant_id', $this->tid())->where(fn ($q) => $q->where('work_email', $user?->email)->orWhere('personal_email', $user?->email))->firstOrFail();
    }
    private function status(mixed $value): ?TenantLookup
    {
        if ($value === null || $value === '') return null;
        $q = TenantLookup::where(fn ($x) => $x->whereNull('tenant_id')->orWhere('tenant_id', $this->tid()));
        return is_numeric($value) ? $q->whereKey((int) $value)->firstOrFail() : $q->where('uuid', (string) $value)->firstOrFail();
    }
    private function payload(AttendanceRecord $row): array
    {
        $row->load(['staff.department', 'status']);
        return ['id' => $row->id, 'staff_uuid' => $row->staff?->uuid, 'staff_name' => $row->staff?->display_name, 'employee_code' => $row->staff?->employee_code, 'attendance_date' => $row->attendance_date, 'check_in_at' => $row->check_in_at, 'check_out_at' => $row->check_out_at, 'total_minutes' => $row->total_minutes, 'status_id' => $row->status_id, 'status_name' => $row->status?->name];
    }
    private function requestPayload(AttendanceRequest $row): array
    {
        $row->load(['staff', 'approvedBy']);
        return ['uuid' => $row->uuid, 'staff_uuid' => $row->staff?->uuid, 'staff_name' => $row->staff?->display_name, 'employee_code' => $row->staff?->employee_code, 'request_date' => $row->request_date, 'request_type' => $row->request_type, 'reason' => $row->reason, 'status' => $row->status, 'approved_by' => $row->approvedBy?->only(['id', 'display_name', 'email']), 'approved_at' => $row->approved_at];
    }
    public function dashboard(): mixed
    {
        $today = today(); $base = AttendanceRecord::where('tenant_id', $this->tid());
        $daily = (clone $base)->with(['staff', 'status'])->whereDate('attendance_date', $today)->latest('id')->limit(20)->get()->map(fn ($r) => $this->payload($r));
        $pending = AttendanceRequest::where('tenant_id', $this->tid())->where('status', 'pending')->with('staff')->latest('id')->limit(20)->get()->map(fn ($r) => $this->requestPayload($r));
        $departments = (clone $base)->join('staff', 'staff.id', '=', 'attendance_records.staff_id')->leftJoin('departments', 'departments.id', '=', 'staff.department_id')->whereDate('attendance_date', $today)->selectRaw("COALESCE(departments.name, 'Unassigned') as department, COUNT(*) as total")->groupBy('departments.name')->get();
        return $this->success(['dashboard' => ['cards' => ['present_today' => (clone $base)->whereDate('attendance_date', $today)->whereNotNull('check_in_at')->count(), 'missing_check_out' => (clone $base)->whereDate('attendance_date', $today)->whereNotNull('check_in_at')->whereNull('check_out_at')->count(), 'pending_corrections' => AttendanceRequest::where('tenant_id', $this->tid())->where('status', 'pending')->count(), 'late_records_this_month' => (clone $base)->whereMonth('attendance_date', now()->month)->whereYear('attendance_date', now()->year)->where('total_minutes', '<', 480)->count()], 'daily' => $daily, 'pending_requests' => $pending, 'department_summary' => $departments]], 'Attendance dashboard fetched.');
    }
    public function daily(TenantAttendanceRequest $request): mixed
    {
        $q = AttendanceRecord::where('tenant_id', $this->tid())->with(['staff', 'status']);
        $date = $request->validated('date', today()->toDateString()); $q->whereDate('attendance_date', $date);
        if ($request->filled('search')) $q->whereHas('staff', fn ($s) => $s->where('display_name', 'like', '%'.$request->validated('search').'%')->orWhere('employee_code', 'like', '%'.$request->validated('search').'%'));
        $page = $q->latest('id')->paginate($request->validated('per_page', 25));
        return $this->success(collect($page->items())->map(fn ($r) => $this->payload($r))->all(), 'Daily attendance fetched.', 200, ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]);
    }
    public function monthly(TenantAttendanceRequest $request): mixed
    {
        $month = (int) $request->validated('month', now()->month); $year = (int) $request->validated('year', now()->year);
        $records = AttendanceRecord::where('tenant_id', $this->tid())->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->with(['staff', 'status'])->orderBy('attendance_date')->get();
        $grid = $records->groupBy('staff_id')->map(function ($items) {
            $first = $items->first(); $days = $items->mapWithKeys(fn ($r) => [Carbon::parse($r->attendance_date)->format('d') => $this->payload($r)]);
            return ['staff_uuid' => $first->staff?->uuid, 'staff_name' => $first->staff?->display_name, 'employee_code' => $first->staff?->employee_code, 'days' => $days, 'present_days' => $items->whereNotNull('check_in_at')->count(), 'total_minutes' => $items->sum('total_minutes')];
        })->values();
        return $this->success(['month' => $month, 'year' => $year, 'grid' => $grid, 'records' => $records->map(fn ($r) => $this->payload($r))], 'Monthly attendance fetched.');
    }
    public function checkIn(): mixed { return $this->check(true); }
    public function checkOut(): mixed
    {
        $staff = $this->currentStaff(); $row = AttendanceRecord::where('tenant_id', $this->tid())->where('staff_id', $staff->id)->whereDate('attendance_date', today())->first();
        if (!$row) return $this->businessError('Check-in record not found.', 'CHECK_IN_NOT_FOUND', 404);
        if ($row->check_out_at) return $this->success(['attendance' => $this->payload($row)], 'Already checked out.');
        $row->update(['check_out_at' => now(), 'total_minutes' => Carbon::parse($row->check_in_at)->diffInMinutes(now())]);
        return $this->success(['attendance' => $this->payload($row->fresh())], 'Checked out.');
    }
    private function check(bool $in): mixed
    {
        $staff = $this->currentStaff(); $row = AttendanceRecord::firstOrCreate(['tenant_id' => $this->tid(), 'staff_id' => $staff->id, 'attendance_date' => today()], ['check_in_at' => now(), 'total_minutes' => 0]);
        if ($in && !$row->check_in_at) $row->update(['check_in_at' => now()]);
        return $this->success(['attendance' => $this->payload($row->fresh())], 'Checked in.', $row->wasRecentlyCreated ? 201 : 200);
    }
    public function storeRecord(TenantAttendanceRequest $request): mixed
    {
        $data = $request->validated(); $staff = $this->staff($data['staff_id']); $status = $this->status($data['status_id'] ?? null);
        $row = AttendanceRecord::create(['tenant_id' => $this->tid(), 'staff_id' => $staff->id, 'attendance_date' => $data['attendance_date'], 'check_in_at' => $data['check_in_at'] ?? null, 'check_out_at' => $data['check_out_at'] ?? null, 'total_minutes' => $this->minutes($data), 'status_id' => $status?->id]);
        return $this->success(['attendance' => $this->payload($row)], 'Attendance marked.', 201);
    }
    public function showRecord(int $record_id): mixed { return $this->success(['attendance' => $this->payload(AttendanceRecord::where('tenant_id', $this->tid())->findOrFail($record_id))], 'Attendance fetched.'); }
    public function updateRecord(TenantAttendanceRequest $request, int $record_id): mixed
    {
        $row = AttendanceRecord::where('tenant_id', $this->tid())->findOrFail($record_id); $data = $request->validated(); $fields = collect($data)->only(['attendance_date', 'check_in_at', 'check_out_at', 'total_minutes'])->all();
        if (array_key_exists('staff_id', $data)) $fields['staff_id'] = $this->staff($data['staff_id'])->id;
        if (array_key_exists('status_id', $data)) $fields['status_id'] = $this->status($data['status_id'])?->id;
        if (array_key_exists('check_in_at', $data) || array_key_exists('check_out_at', $data)) $fields['total_minutes'] = $this->minutes($data + $row->toArray());
        $row->update($fields);
        return $this->success(['attendance' => $this->payload($row->fresh())], 'Attendance updated.');
    }
    public function storeRequest(TenantAttendanceRequest $request): mixed
    {
        $data = $request->validated(); $staff = $this->staff($data['staff_id'] ?? $this->currentStaff()->id);
        $row = AttendanceRequest::create(['uuid' => (string) Str::uuid(), 'tenant_id' => $this->tid(), 'staff_id' => $staff->id, 'request_date' => $data['request_date'] ?? today(), 'request_type' => $data['request_type'], 'reason' => $data['reason'] ?? null, 'status' => 'pending']);
        return $this->success(['request' => $this->requestPayload($row)], 'Correction requested.', 201);
    }
    public function requests(TenantAttendanceRequest $request): mixed
    {
        $q = AttendanceRequest::where('tenant_id', $this->tid())->with(['staff', 'approvedBy']);
        if ($request->filled('search')) $q->where(function ($search) use ($request) { $term = '%'.$request->validated('search').'%'; $search->whereHas('staff', fn ($s) => $s->where('display_name', 'like', $term)->orWhere('employee_code', 'like', $term))->orWhere('request_type', 'like', $term)->orWhere('reason', 'like', $term); });
        $page = $q->latest('id')->paginate($request->validated('per_page', 25));
        return $this->success(collect($page->items())->map(fn ($r) => $this->requestPayload($r))->all(), 'Attendance requests fetched.', 200, ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]);
    }
    public function requestShow(string $request_uuid): mixed { return $this->success(['request' => $this->requestPayload(AttendanceRequest::where('tenant_id', $this->tid())->where('uuid', $request_uuid)->firstOrFail())], 'Attendance request fetched.'); }
    public function review(string $request_uuid, string $status): mixed
    {
        $row = AttendanceRequest::where('tenant_id', $this->tid())->where('uuid', $request_uuid)->firstOrFail(); $row->update(['status' => $status, 'approved_by' => request()->user()->id, 'approved_at' => now()]);
        return $this->success(['request' => $this->requestPayload($row->fresh())], 'Attendance request '.($status === 'approved' ? 'approved' : 'rejected').'.');
    }
    public function import(Request $request): mixed { return $this->queue($request, 'import'); }
    public function export(TenantAttendanceRequest $request): mixed { return $this->queue($request, 'export'); }
    private function queue(Request $request, string $type): mixed
    {
        $id = DB::table('tenant_import_export_jobs')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $this->tid(), 'user_id' => $request->user()?->id, 'type' => $type, 'module' => 'attendance', 'status' => 'queued', 'payload' => json_encode($request->all()), 'created_at' => now(), 'updated_at' => now()]);
        return $this->success(['job' => DB::table('tenant_import_export_jobs')->where('id', $id)->first()], 'Attendance '.$type.' queued.', 202);
    }
    private function minutes(array $data): int { return !empty($data['check_in_at']) && !empty($data['check_out_at']) ? max(0, Carbon::parse($data['check_in_at'])->diffInMinutes(Carbon::parse($data['check_out_at']))) : (int) ($data['total_minutes'] ?? 0); }
    private function leaveType(mixed $value): LeaveType
    {
        $query = LeaveType::where('tenant_id', $this->tid());
        return is_numeric($value) ? $query->whereKey((int) $value)->firstOrFail() : $query->where('uuid', (string) $value)->firstOrFail();
    }

    private function leavePayload(LeaveRequest $row): array
    {
        $row->load(['staff', 'leaveType']);
        return ['id'=>$row->id,'staff_uuid'=>$row->staff?->uuid,'staff_name'=>$row->staff?->display_name,'employee_code'=>$row->staff?->employee_code,'leave_type_uuid'=>$row->leaveType?->uuid,'leave_type_name'=>$row->leaveType?->name,'start_date'=>$row->start_date,'end_date'=>$row->end_date,'total_days'=>$row->total_days,'reason'=>$row->reason,'status'=>$row->status,'status_name'=>$row->status ? ucfirst(str_replace('_',' ',$row->status)) : null,'approved_by'=>$row->approvedBy?->only(['id','display_name','email']),'approved_at'=>$row->approved_at];
    }

    private function balancePayload(LeaveBalance $row): array
    {
        $row->load(['staff', 'leaveType']);
        return ['id'=>$row->id,'staff_uuid'=>$row->staff?->uuid,'staff_name'=>$row->staff?->display_name,'employee_code'=>$row->staff?->employee_code,'leave_type_uuid'=>$row->leaveType?->uuid,'leave_type_name'=>$row->leaveType?->name,'year'=>$row->year,'opening_balance'=>$row->opening_balance,'accrued'=>$row->accrued,'used'=>$row->used,'remaining'=>$row->remaining];
    }

    public function leaveDashboard(): mixed
    {
        $base=LeaveRequest::where('tenant_id',$this->tid());
        $pending=(clone $base)->where('status','pending')->with(['staff','leaveType'])->latest('id')->limit(20)->get()->map(fn($r)=>$this->leavePayload($r));
        $balances=LeaveBalance::where('tenant_id',$this->tid())->with(['staff','leaveType'])->where('remaining','<',1)->latest('id')->limit(20)->get()->map(fn($r)=>$this->balancePayload($r));
        $calendar=(clone $base)->where('status','approved')->with(['staff','leaveType'])->whereDate('end_date','>=',today())->orderBy('start_date')->limit(20)->get()->map(fn($r)=>$this->leavePayload($r));
        return $this->success(['dashboard'=>['cards'=>['pending_requests'=>(clone $base)->where('status','pending')->count(),'approved_this_month'=>(clone $base)->where('status','approved')->whereMonth('approved_at',now()->month)->whereYear('approved_at',now()->year)->count(),'leave_types'=>LeaveType::where('tenant_id',$this->tid())->where('status','active')->count(),'low_balances'=>LeaveBalance::where('tenant_id',$this->tid())->where('remaining','<',1)->count()],'pending_requests'=>$pending,'balances'=>$balances,'calendar'=>$calendar]],'Leave dashboard fetched.');
    }

    public function leaveRequests(TenantAttendanceRequest $request): mixed
    {
        $q=LeaveRequest::where('tenant_id',$this->tid())->with(['staff','leaveType','approvedBy']);
        if($request->filled('search')){$term='%'.$request->validated('search').'%';$q->where(function($x)use($term){$x->whereHas('staff',fn($s)=>$s->where('display_name','like',$term)->orWhere('employee_code','like',$term))->orWhereHas('leaveType',fn($t)=>$t->where('name','like',$term))->orWhere('reason','like',$term);});}
        $page=$q->latest('id')->paginate($request->validated('per_page',25));
        return $this->success(collect($page->items())->map(fn($r)=>$this->leavePayload($r))->all(),'Leave requests fetched.',200,['current_page'=>$page->currentPage(),'per_page'=>$page->perPage(),'total'=>$page->total(),'last_page'=>$page->lastPage()]);
    }

    public function storeLeaveRequest(TenantAttendanceRequest $request): mixed
    {
        $d=$request->validated();$staff=$this->staff($d['staff_id']??$this->currentStaff()->id);$type=$this->leaveType($d['leave_type_id']);
        $overlap=LeaveRequest::where('tenant_id',$this->tid())->where('staff_id',$staff->id)->whereIn('status',['pending','approved'])->whereDate('start_date','<=',$d['end_date'])->whereDate('end_date','>=',$d['start_date'])->exists();
        if($overlap)return $this->businessError('A leave request overlaps an existing pending or approved request.','LEAVE_OVERLAP',409);
        $row=LeaveRequest::create(['tenant_id'=>$this->tid(),'staff_id'=>$staff->id,'leave_type_id'=>$type->id,'start_date'=>$d['start_date'],'end_date'=>$d['end_date'],'total_days'=>$d['total_days'],'reason'=>$d['reason']??null,'status'=>'pending']);
        return $this->success(['request'=>$this->leavePayload($row)],'Leave requested.',201);
    }

    public function leaveRequestShow(int $request_id): mixed
    {
        $row=LeaveRequest::where('tenant_id',$this->tid())->findOrFail($request_id);
        return $this->success(['request'=>$this->leavePayload($row)],'Leave request fetched.');
    }

    public function reviewLeave(int $request_id,string $status): mixed
    {
        return DB::transaction(function()use($request_id,$status){
            $row=LeaveRequest::where('tenant_id',$this->tid())->lockForUpdate()->findOrFail($request_id);
            if($row->status!=='pending')return $this->businessError('Only pending leave requests can be reviewed.','LEAVE_NOT_PENDING',409);
            if($status==='approved'){$b=LeaveBalance::where('tenant_id',$this->tid())->where('staff_id',$row->staff_id)->where('leave_type_id',$row->leave_type_id)->where('year',Carbon::parse($row->start_date)->year)->lockForUpdate()->first();if(!$b||(float)$b->remaining<(float)$row->total_days)return $this->businessError('Insufficient leave balance.','INSUFFICIENT_LEAVE_BALANCE',409);$b->update(['used'=>(float)$b->used+(float)$row->total_days,'remaining'=>(float)$b->remaining-(float)$row->total_days]);}
            $row->update(['status'=>$status,'approved_by'=>request()->user()?->id,'approved_at'=>now()]);
            return $this->success(['request'=>$this->leavePayload($row->fresh())],'Leave request '.$status.'.');
        });
    }

    public function cancelLeave(int $request_id): mixed
    {
        return DB::transaction(function()use($request_id){
            $row=LeaveRequest::where('tenant_id',$this->tid())->lockForUpdate()->findOrFail($request_id);
            if(!in_array($row->status,['pending','approved'],true))return $this->businessError('Only pending or approved leave can be cancelled.','LEAVE_NOT_CANCELLABLE',409);
            if($row->status==='approved'){$b=LeaveBalance::where('tenant_id',$this->tid())->where('staff_id',$row->staff_id)->where('leave_type_id',$row->leave_type_id)->where('year',Carbon::parse($row->start_date)->year)->lockForUpdate()->first();if($b)$b->update(['used'=>max(0,(float)$b->used-(float)$row->total_days),'remaining'=>(float)$b->remaining+(float)$row->total_days]);}
            $row->update(['status'=>'cancelled','approved_by'=>null,'approved_at'=>null]);
            return $this->success(['request'=>$this->leavePayload($row->fresh())],'Leave request cancelled.');
        });
    }

    public function leaveBalances(TenantAttendanceRequest $request): mixed
    {
        $q=LeaveBalance::where('tenant_id',$this->tid())->with(['staff','leaveType']);
        if($request->filled('search')){$term='%'.$request->validated('search').'%';$q->where(function($x)use($term){$x->whereHas('staff',fn($s)=>$s->where('display_name','like',$term)->orWhere('employee_code','like',$term))->orWhereHas('leaveType',fn($t)=>$t->where('name','like',$term));});}
        $page=$q->latest('id')->paginate($request->validated('per_page',25));
        return $this->success(collect($page->items())->map(fn($r)=>$this->balancePayload($r))->all(),'Leave balances fetched.',200,['current_page'=>$page->currentPage(),'per_page'=>$page->perPage(),'total'=>$page->total(),'last_page'=>$page->lastPage()]);
    }

    public function adjustLeaveBalance(TenantAttendanceRequest $request): mixed
    {
        $d=$request->validated();$staff=$this->staff($d['staff_id']);$type=$this->leaveType($d['leave_type_id']);
        $row=LeaveBalance::updateOrCreate(['tenant_id'=>$this->tid(),'staff_id'=>$staff->id,'leave_type_id'=>$type->id,'year'=>$d['year']??now()->year],['remaining'=>$d['remaining']]);
        return $this->success(['balances'=>[$this->balancePayload($row)]],'Leave balance adjusted.');
    }

    public function leaveCalendar(TenantAttendanceRequest $request): mixed
    {
        $q=LeaveRequest::where('tenant_id',$this->tid())->where('status','approved')->with(['staff','leaveType'])->whereDate('end_date','>=',$request->validated('date_from',today()->toDateString()));
        if($request->filled('date_to'))$q->whereDate('start_date','<=',$request->validated('date_to'));
        if($request->filled('staff_id'))$q->where('staff_id',$this->staff($request->validated('staff_id'))->id);
        if($request->filled('leave_type_id'))$q->where('leave_type_id',$this->leaveType($request->validated('leave_type_id'))->id);
        return $this->success(['events'=>$q->orderBy('start_date')->limit(500)->get()->map(fn($r)=>$this->leavePayload($r))],'Leave calendar fetched.');
    }

    public function leaveTypes(): mixed
    {
        return $this->success(['leave_types'=>LeaveType::where('tenant_id',$this->tid())->where('status','active')->orderBy('name')->get()],'Leave types fetched.');
    }

    public function storeLeaveType(TenantAttendanceRequest $request): mixed
    {
        $d=$request->validated();$row=LeaveType::create(['tenant_id'=>$this->tid(),'uuid'=>(string)Str::uuid(),'name'=>$d['name'],'code'=>$d['code'],'paid'=>$d['paid']??true,'carry_forward'=>$d['carry_forward']??false,'status'=>$d['status']??'active']);
        return $this->success(['leave_type'=>$row],'Leave type created.',201);
    }


    private function payrollCycle(int|string $value): object
    {
        $q=DB::table('payroll_cycles')->where('tenant_id',$this->tid());
        return is_numeric($value)?$q->where('id',(int)$value)->firstOrFail():$q->where('uuid',(string)$value)->firstOrFail();
    }
    private function payrollCycleBundle(object $cycle): array
    {
        $rows=DB::table('payrolls as p')->leftJoin('staff as s','s.id','=','p.staff_id')->where('p.tenant_id',$this->tid())->where('p.payroll_cycle_id',$cycle->id)->select('p.*','s.uuid as staff_uuid','s.display_name as staff_name')->get()->map(fn($p)=>$this->payrollPayload($p))->all();
        return ['cycle'=>(array)$cycle,'payrolls'=>$rows];
    }
    private function payrollPayload(object $row): array
    {
        return ['uuid'=>$row->uuid,'staff_uuid'=>$row->staff_uuid??null,'staff_name'=>$row->staff_name??null,'employee_code'=>$row->employee_code??null,'cycle_uuid'=>$row->cycle_uuid??null,'cycle_name'=>$row->cycle_name??null,'working_days'=>$row->working_days,'present_days'=>$row->present_days,'leave_days'=>$row->leave_days,'unpaid_leave_days'=>$row->unpaid_leave_days,'overtime_hours'=>$row->overtime_hours,'gross_salary'=>$row->gross_salary,'total_earnings'=>$row->total_earnings,'total_deductions'=>$row->total_deductions,'taxable_income'=>$row->taxable_income,'tax_amount'=>$row->tax_amount,'net_salary'=>$row->net_salary,'payment_status'=>$row->payment_status,'payment_reference'=>$row->payment_reference,'remarks'=>$row->remarks];
    }
    private function payrollPage($page, callable $map): mixed
    {
        return $this->success(collect($page->items())->map($map)->all(),'Payroll data fetched.',200,['current_page'=>$page->currentPage(),'per_page'=>$page->perPage(),'total'=>$page->total(),'last_page'=>$page->lastPage()]);
    }
    public function payrollDashboard(): mixed
    {
        $cycles=DB::table('payroll_cycles')->where('tenant_id',$this->tid())->latest('id');
        $recent=DB::table('payrolls as p')->leftJoin('staff as s','s.id','=','p.staff_id')->leftJoin('payroll_cycles as c','c.id','=','p.payroll_cycle_id')->where('p.tenant_id',$this->tid())->latest('p.id')->limit(10)->select('p.*','s.uuid as staff_uuid','s.display_name as staff_name','c.uuid as cycle_uuid','c.cycle_name')->get()->map(fn($r)=>$this->payrollPayload($r));
        return $this->success(['dashboard'=>['cards'=>['cycles'=>(clone $cycles)->count(),'gross_payroll'=>DB::table('payrolls')->where('tenant_id',$this->tid())->sum('gross_salary'),'net_payroll'=>DB::table('payrolls')->where('tenant_id',$this->tid())->sum('net_salary'),'pending_reimbursements'=>DB::table('payroll_reimbursements')->where('tenant_id',$this->tid())->where('approval_status','pending')->count(),'pending_bank_transfers'=>DB::table('payroll_bank_transfers')->where('tenant_id',$this->tid())->where('status','pending')->count()],'current_cycles'=>(clone $cycles)->limit(8)->get(),'recent_payrolls'=>$recent,'component_breakdown'=>DB::table('payroll_items as i')->join('payroll_components as c','c.id','=','i.component_id')->where('i.tenant_id',$this->tid())->select('c.name',DB::raw('SUM(i.amount) as amount'))->groupBy('c.id','c.name')->get()]],'Payroll dashboard fetched.');
    }
    public function payrollCycles(Request $request): mixed
    {
        $q=DB::table('payroll_cycles')->where('tenant_id',$this->tid());
        if($request->filled('search')){$term='%'.$request->input('search').'%';$q->where(fn($x)=>$x->where('cycle_name','like',$term)->orWhere('status','like',$term));}
        return $this->payrollPage($q->latest('id')->paginate((int)$request->input('per_page',25)),fn($r)=>(array)$r);
    }
    public function storePayrollCycle(Request $request): mixed
    {
        $d=$request->validate(['cycle_name'=>'required|string|max:255','payroll_month'=>'required|integer|between:1,12','payroll_year'=>'required|integer|between:2000,2100','period_start'=>'required|date','period_end'=>'required|date|after_or_equal:period_start','payment_date'=>'nullable|date','status'=>'nullable|string','remarks'=>'nullable|string']);
        if(DB::table('payroll_cycles')->where('tenant_id',$this->tid())->where('payroll_month',$d['payroll_month'])->where('payroll_year',$d['payroll_year'])->exists())return $this->businessError('A payroll cycle already exists for this month and year.','PAYROLL_CYCLE_EXISTS',409);
        $id=DB::table('payroll_cycles')->insertGetId($d+['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tid(),'status'=>'draft','created_at'=>now(),'updated_at'=>now()]);
        $cycle=DB::table('payroll_cycles')->where('id',$id)->first();
        return $this->success(['cycle'=>$this->payrollCycleBundle($cycle)],'Payroll cycle created.',201);
    }
    public function showPayrollCycle(string $cycle_uuid): mixed { return $this->success(['cycle'=>$this->payrollCycleBundle($this->payrollCycle($cycle_uuid))],'Payroll cycle fetched.'); }
    public function updatePayrollCycle(Request $request,string $cycle_uuid): mixed
    {
        $cycle=$this->payrollCycle($cycle_uuid); if($cycle->status==='locked')return $this->businessError('Locked payroll cycles cannot be edited.','PAYROLL_LOCKED',409);
        $d=$request->validate(['cycle_name'=>'sometimes|string|max:255','payment_date'=>'sometimes|nullable|date','period_start'=>'sometimes|date','period_end'=>'sometimes|date|after_or_equal:period_start','remarks'=>'sometimes|nullable|string','status'=>'sometimes|string']);
        DB::table('payroll_cycles')->where('id',$cycle->id)->update($d+['updated_at'=>now()]);
        return $this->success(['cycle'=>$this->payrollCycleBundle($this->payrollCycle($cycle->id))],'Payroll cycle updated.');
    }
    private function previewRows(object $cycle,array $staffIds=[]): array
    {
        $staff=DB::table('staff')->where('tenant_id',$this->tid())->when($staffIds,fn($q)=>$q->whereIn('uuid',$staffIds))->limit(100)->get();
        return $staff->map(function($s)use($cycle){$present=DB::table('attendance_records')->where('tenant_id',$this->tid())->where('staff_id',$s->id)->whereBetween('attendance_date',[$cycle->period_start,$cycle->period_end])->whereNotNull('check_in_at')->count();$leave=DB::table('leave_requests')->where('tenant_id',$this->tid())->where('staff_id',$s->id)->where('status','approved')->whereDate('start_date','<=',$cycle->period_end)->whereDate('end_date','>=',$cycle->period_start)->sum('total_days');return ['staff_uuid'=>$s->uuid,'staff_name'=>$s->display_name,'employee_code'=>$s->employee_code,'working_days'=>26,'present_days'=>$present,'leave_days'=>$leave,'gross_salary'=>0,'total_earnings'=>0,'total_deductions'=>0,'net_salary'=>0];})->all();
    }
    public function payrollPreview(Request $request,string $cycle_uuid): mixed { $cycle=$this->payrollCycle($cycle_uuid); return $this->success(['preview'=>$this->previewRows($cycle,(array)$request->input('staff_ids',[])),'validations'=>['missing_salary_structures'=>0,'cycle_period'=>[$cycle->period_start,$cycle->period_end]]],'Payroll preview generated.'); }
    public function generatePayroll(Request $request,string $cycle_uuid): mixed
    {
        $cycle=$this->payrollCycle($cycle_uuid); if(in_array($cycle->status,['locked','approved'],true))return $this->businessError('This payroll cycle cannot be generated in its current state.','PAYROLL_STATE',409);
        return DB::transaction(function()use($cycle,$request){foreach($this->previewRows($cycle,(array)$request->input('staff_ids',[])) as $p){$staff=DB::table('staff')->where('tenant_id',$this->tid())->where('uuid',$p['staff_uuid'])->first();DB::table('payrolls')->updateOrInsert(['tenant_id'=>$this->tid(),'payroll_cycle_id'=>$cycle->id,'staff_id'=>$staff->id],['uuid'=>(string)Str::uuid(),'employee_code'=>$staff->employee_code,'working_days'=>$p['working_days'],'present_days'=>$p['present_days'],'leave_days'=>$p['leave_days'],'gross_salary'=>$p['gross_salary'],'total_earnings'=>$p['total_earnings'],'total_deductions'=>$p['total_deductions'],'net_salary'=>$p['net_salary'],'updated_at'=>now(),'created_at'=>now()]);}DB::table('payroll_cycles')->where('id',$cycle->id)->update(['status'=>'generated','processed_by'=>request()->user()?->id,'processed_at'=>now(),'updated_at'=>now()]);return $this->success(['cycle'=>$this->payrollCycleBundle($this->payrollCycle($cycle->id))],'Payroll generated.');});
    }
    public function payrollCycleAction(Request $request,string $cycle_uuid,string $action): mixed
    {
        $cycle=$this->payrollCycle($cycle_uuid);$map=['submit'=>'submitted','approve'=>'approved','lock'=>'locked','reopen'=>'draft'];if(!isset($map[$action]))return $this->businessError('Unsupported payroll action.','PAYROLL_ACTION',422);if($action==='approve'&&!in_array($cycle->status,['generated','submitted'],true))return $this->businessError('Payroll must be generated or submitted before approval.','PAYROLL_STATE',409);$d=['status'=>$map[$action],'updated_at'=>now()];if($action==='approve')$d+=['approved_by'=>request()->user()?->id,'approved_at'=>now()];DB::table('payroll_cycles')->where('id',$cycle->id)->update($d);return $this->success(['cycle'=>$this->payrollCycleBundle($this->payrollCycle($cycle->id))],'Payroll cycle '.$action.'.');
    }
    public function payrolls(Request $request): mixed
    {
        $q=DB::table('payrolls as p')->leftJoin('staff as s','s.id','=','p.staff_id')->leftJoin('payroll_cycles as c','c.id','=','p.payroll_cycle_id')->where('p.tenant_id',$this->tid())->select('p.*','s.uuid as staff_uuid','s.display_name as staff_name','c.uuid as cycle_uuid','c.cycle_name');
        if($request->filled('search')){$term='%'.$request->input('search').'%';$q->where(fn($x)=>$x->where('s.display_name','like',$term)->orWhere('s.employee_code','like',$term));}
        return $this->payrollPage($q->latest('p.id')->paginate((int)$request->input('per_page',25)),fn($r)=>$this->payrollPayload($r));
    }
    public function showPayroll(string $payroll_uuid): mixed
    {
        $p=DB::table('payrolls as p')->leftJoin('staff as s','s.id','=','p.staff_id')->leftJoin('payroll_cycles as c','c.id','=','p.payroll_cycle_id')->where('p.tenant_id',$this->tid())->where('p.uuid',$payroll_uuid)->select('p.*','s.uuid as staff_uuid','s.display_name as staff_name','c.uuid as cycle_uuid','c.cycle_name')->firstOrFail();
        return $this->success(['payroll'=>['payroll'=>$this->payrollPayload($p),'items'=>$this->payrollItemsData($p->id),'overtime'=>DB::table('payroll_overtime')->where('tenant_id',$this->tid())->where('payroll_id',$p->id)->get(),'approvals'=>DB::table('payroll_approvals')->where('tenant_id',$this->tid())->where('payroll_id',$p->id)->get(),'payslip'=>DB::table('payroll_payslips')->where('tenant_id',$this->tid())->where('payroll_id',$p->id)->first()]],'Payroll fetched.');
    }
    public function updatePayroll(Request $request,string $payroll_uuid): mixed
    {
        $p=DB::table('payrolls as p')->join('payroll_cycles as c','c.id','=','p.payroll_cycle_id')->where('p.tenant_id',$this->tid())->where('p.uuid',$payroll_uuid)->select('p.*','c.status as cycle_status')->firstOrFail();if($p->cycle_status==='locked')return $this->businessError('Locked payroll cannot be edited.','PAYROLL_LOCKED',409);$d=$request->validate(['working_days'=>'sometimes|numeric|min:0','present_days'=>'sometimes|numeric|min:0','leave_days'=>'sometimes|numeric|min:0','unpaid_leave_days'=>'sometimes|numeric|min:0','overtime_hours'=>'sometimes|numeric|min:0','gross_salary'=>'sometimes|numeric|min:0','total_earnings'=>'sometimes|numeric|min:0','total_deductions'=>'sometimes|numeric|min:0','taxable_income'=>'sometimes|numeric|min:0','tax_amount'=>'sometimes|numeric|min:0','net_salary'=>'sometimes|numeric|min:0','payment_status'=>'sometimes|string','payment_reference'=>'sometimes|nullable|string','remarks'=>'sometimes|nullable|string']);DB::table('payrolls')->where('id',$p->id)->update($d+['updated_at'=>now()]);return $this->showPayroll($payroll_uuid);
    }
    private function payrollItemsData(int $id): array { return DB::table('payroll_items as i')->leftJoin('payroll_components as c','c.id','=','i.component_id')->where('i.tenant_id',$this->tid())->where('i.payroll_id',$id)->select('i.*','c.uuid as component_uuid','c.name as component_name','c.code as component_code')->get()->all(); }
    public function payrollItems(string $payroll_uuid): mixed { $p=DB::table('payrolls')->where('tenant_id',$this->tid())->where('uuid',$payroll_uuid)->firstOrFail();return $this->success(['items'=>$this->payrollItemsData($p->id)],'Payroll items fetched.'); }
    public function payslips(Request $request): mixed { $q=DB::table('payroll_payslips as s')->join('payrolls as p','p.id','=','s.payroll_id')->join('staff as st','st.id','=','p.staff_id')->where('s.tenant_id',$this->tid())->select('s.*','st.display_name as staff_name','st.employee_code');return $this->payrollPage($q->latest('s.id')->paginate((int)$request->input('per_page',25)),fn($r)=>(array)$r); }
    private function queuePayrollJob(Request $request,string $type): mixed { $id=DB::table('tenant_import_export_jobs')->insertGetId(['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tid(),'user_id'=>$request->user()?->id,'type'=>$type,'module'=>'payroll','status'=>'queued','payload'=>json_encode($request->all()),'created_at'=>now(),'updated_at'=>now()]);return $this->success(['job'=>DB::table('tenant_import_export_jobs')->where('id',$id)->first()],'Payroll '.$type.' queued.',202); }
    public function generatePayslips(Request $request): mixed { return $this->queuePayrollJob($request,'generate'); }
    public function emailPayslips(Request $request): mixed { return $this->queuePayrollJob($request,'email'); }
    public function downloadPayslip(int $payslip_id): mixed { $p=DB::table('payroll_payslips')->where('tenant_id',$this->tid())->find($payslip_id);if(!$p)return $this->businessError('Payslip not found.','NOT_FOUND',404);return $this->success(['payslip'=>$p,'download'=>['status'=>'metadata_only','message'=>'File download is available when a payslip file is generated.']],'Payslip metadata fetched.'); }
    private function configRows(string $table,string $key): mixed
    {
        $query=DB::table($table)->where($table.'.tenant_id',$this->tid());
        if($table==='payroll_components')$query->leftJoin('payroll_component_types as ct','ct.id','=','payroll_components.component_type_id')->select('payroll_components.*','ct.uuid as component_type_uuid','ct.name as component_type_name','ct.code as component_type_code');
        elseif($table==='payroll_component_assignments')$query->leftJoin('staff as s','s.id','=','payroll_component_assignments.staff_id')->leftJoin('payroll_components as c','c.id','=','payroll_component_assignments.component_id')->select('payroll_component_assignments.*','s.uuid as staff_uuid','s.display_name as staff_name','s.employee_code','c.uuid as component_uuid','c.name as component_name','c.code as component_code');
        elseif($table==='payroll_loans')$query->leftJoin('staff as s','s.id','=','payroll_loans.staff_id')->select('payroll_loans.*','s.uuid as staff_uuid','s.display_name as staff_name','s.employee_code');
        elseif($table==='payroll_reimbursements')$query->leftJoin('staff as s','s.id','=','payroll_reimbursements.staff_id')->select('payroll_reimbursements.*','s.uuid as staff_uuid','s.display_name as staff_name','s.employee_code');
        elseif($table==='payroll_bank_transfers')$query->leftJoin('payrolls as p','p.id','=','payroll_bank_transfers.payroll_id')->leftJoin('staff as s','s.id','=','p.staff_id')->select('payroll_bank_transfers.*','p.uuid as payroll_uuid','s.uuid as staff_uuid','s.display_name as staff_name','s.employee_code');
        return $this->success([$key=>$query->get()],$key.' fetched.');
    }
    public function componentTypes(): mixed { return $this->configRows('payroll_component_types','component_types'); }
    public function storeComponentType(Request $r): mixed { $d=$r->validate(['name'=>'required|string','code'=>'required|string','calculation_side'=>'required|string','status'=>'nullable|string']);$id=DB::table('payroll_component_types')->insertGetId($d+['tenant_id'=>$this->tid(),'status'=>$d['status']??'active']);return $this->success(['component_type'=>DB::table('payroll_component_types')->find($id)],'Component type created.',201); }
    public function components(): mixed { return $this->configRows('payroll_components','components'); }
    public function storeComponent(Request $r): mixed { $d=$r->validate(['component_type_id'=>'required','name'=>'required|string','code'=>'required|string','calculation_method'=>'required|string','default_value'=>'nullable|numeric|min:0','formula'=>'nullable|string','taxable'=>'nullable|boolean','affects_pf'=>'nullable|boolean','affects_esi'=>'nullable|boolean','status'=>'nullable|string']);$type=DB::table('payroll_component_types')->where('tenant_id',$this->tid())->where(fn($q)=>is_numeric($d['component_type_id'])?$q->where('id',$d['component_type_id']):$q->where('uuid',$d['component_type_id']))->firstOrFail();$id=DB::table('payroll_components')->insertGetId(collect($d)->except('component_type_id')->all()+['tenant_id'=>$this->tid(),'component_type_id'=>$type->id]);return $this->success(['component'=>DB::table('payroll_components')->find($id)],'Component created.',201); }
    public function updateComponent(Request $r,int $component_id): mixed { $d=$r->validate(['name'=>'sometimes|string','calculation_method'=>'sometimes|string','default_value'=>'sometimes|numeric|min:0','formula'=>'sometimes|nullable|string','taxable'=>'sometimes|boolean','affects_pf'=>'sometimes|boolean','affects_esi'=>'sometimes|boolean','status'=>'sometimes|string']);$q=DB::table('payroll_components')->where('tenant_id',$this->tid())->where('id',$component_id);if(!$q->exists())abort(404);$q->update($d);return $this->success(['component'=>$q->first()],'Component updated.'); }
    public function assignments(): mixed { return $this->configRows('payroll_component_assignments','assignments'); }
    public function storeAssignment(Request $r): mixed { $d=$r->validate(['staff_id'=>'required','component_id'=>'required','amount'=>'required|numeric|min:0','effective_from'=>'required|date','effective_to'=>'nullable|date|after_or_equal:effective_from']);$staff=$this->staff($d['staff_id']);$c=DB::table('payroll_components')->where('tenant_id',$this->tid())->where(fn($q)=>is_numeric($d['component_id'])?$q->where('id',$d['component_id']):$q->where('uuid',$d['component_id']))->firstOrFail();$id=DB::table('payroll_component_assignments')->insertGetId(['tenant_id'=>$this->tid(),'staff_id'=>$staff->id,'component_id'=>$c->id,'amount'=>$d['amount'],'effective_from'=>$d['effective_from'],'effective_to'=>$d['effective_to']??null]);return $this->success(['assignment'=>DB::table('payroll_component_assignments')->find($id)],'Component assignment created.',201); }
    public function loans(): mixed { return $this->configRows('payroll_loans','loans'); }
    public function storeLoan(Request $r): mixed { $d=$r->validate(['staff_id'=>'required','loan_number'=>'required|string','principal_amount'=>'required|numeric|min:0','interest_rate'=>'required|numeric|min:0','installment_amount'=>'required|numeric|min:0','remaining_amount'=>'required|numeric|min:0','total_installments'=>'required|integer|min:0','issued_date'=>'required|date','status'=>'nullable|string']);$d['staff_id']=$this->staff($d['staff_id'])->id;$d['tenant_id']=$this->tid();$id=DB::table('payroll_loans')->insertGetId($d);return $this->success(['loan'=>DB::table('payroll_loans')->find($id)],'Loan created.',201); }
    public function updateLoan(Request $r,int $loan_id): mixed { $d=$r->all();if(isset($d['staff_id']))$d['staff_id']=$this->staff($d['staff_id'])->id;DB::table('payroll_loans')->where('tenant_id',$this->tid())->where('id',$loan_id)->update($d);return $this->success(['loan'=>DB::table('payroll_loans')->where('tenant_id',$this->tid())->find($loan_id)],'Loan updated.'); }
    public function reimbursements(): mixed { return $this->configRows('payroll_reimbursements','reimbursements'); }
    public function storeReimbursement(Request $r): mixed { $d=$r->validate(['payroll_id'=>'nullable|integer','staff_id'=>'required','expense_id'=>'nullable|integer','amount'=>'required|numeric|min:0','approval_status'=>'nullable|string']);$d['staff_id']=$this->staff($d['staff_id'])->id;$d['tenant_id']=$this->tid();$id=DB::table('payroll_reimbursements')->insertGetId($d);return $this->success(['reimbursement'=>DB::table('payroll_reimbursements')->find($id)],'Reimbursement created.',201); }
    public function approveReimbursement(Request $r,int $id): mixed { DB::table('payroll_reimbursements')->where('tenant_id',$this->tid())->where('id',$id)->update(['approval_status'=>$r->input('status','approved')]);return $this->success(['reimbursement'=>DB::table('payroll_reimbursements')->where('tenant_id',$this->tid())->find($id)],'Reimbursement updated.'); }
    public function bankTransfers(): mixed { return $this->configRows('payroll_bank_transfers','bank_transfers'); }
    public function storeBankTransfer(Request $r): mixed { $d=$r->validate(['payroll_id'=>'required|integer','bank_account_id'=>'nullable|integer','reference'=>'nullable|string','amount'=>'required|numeric|min:0','transfer_date'=>'nullable|date','status'=>'nullable|string']);$d['tenant_id']=$this->tid();$id=DB::table('payroll_bank_transfers')->insertGetId($d);return $this->success(['transfer'=>DB::table('payroll_bank_transfers')->find($id)],'Bank transfer created.',201); }
    public function markTransferPaid(Request $r,int $id): mixed { DB::table('payroll_bank_transfers')->where('tenant_id',$this->tid())->where('id',$id)->update(['status'=>'paid','transfer_date'=>$r->input('transfer_date',today()->toDateString()),'reference'=>$r->input('reference')]);return $this->success(['transfer'=>DB::table('payroll_bank_transfers')->where('tenant_id',$this->tid())->find($id)],'Bank transfer marked paid.'); }
    public function taxSlabs(): mixed { return $this->configRows('payroll_tax_slabs','tax_slabs'); }
    public function storeTaxSlab(Request $r): mixed { $d=$r->validate(['name'=>'required|string','min_amount'=>'required|numeric|min:0','max_amount'=>'nullable|numeric','tax_percentage'=>'required|numeric|min:0|max:100','cess_percentage'=>'nullable|numeric|min:0|max:100','effective_from'=>'required|date','effective_to'=>'nullable|date|after_or_equal:effective_from']);$id=DB::table('payroll_tax_slabs')->insertGetId($d+['tenant_id'=>$this->tid()]);return $this->success(['tax_slab'=>DB::table('payroll_tax_slabs')->find($id)],'Tax slab created.',201); }
    public function pfSettings(): mixed { return $this->success(['pf_settings'=>DB::table('payroll_pf_settings')->where('tenant_id',$this->tid())->latest('id')->first()],'PF settings fetched.'); }
    public function updatePfSettings(Request $r): mixed { $d=$r->validate(['employee_rate'=>'required|numeric|min:0','employer_rate'=>'required|numeric|min:0','wage_limit'=>'nullable|numeric|min:0','effective_from'=>'required|date']);$id=DB::table('payroll_pf_settings')->insertGetId($d+['tenant_id'=>$this->tid()]);return $this->success(['pf_settings'=>DB::table('payroll_pf_settings')->find($id)],'PF settings updated.'); }
    public function esiSettings(): mixed { return $this->success(['esi_settings'=>DB::table('payroll_esi_settings')->where('tenant_id',$this->tid())->latest('id')->first()],'ESI settings fetched.'); }
    public function updateEsiSettings(Request $r): mixed { $d=$r->validate(['employee_rate'=>'required|numeric|min:0','employer_rate'=>'required|numeric|min:0','wage_limit'=>'nullable|numeric|min:0','effective_from'=>'required|date']);$id=DB::table('payroll_esi_settings')->insertGetId($d+['tenant_id'=>$this->tid()]);return $this->success(['esi_settings'=>DB::table('payroll_esi_settings')->find($id)],'ESI settings updated.'); }
    public function payrollExport(Request $r): mixed { return $this->queuePayrollJob($r,'export'); }


    private function holidayCalendar(mixed $value): object
    {
        $q=DB::table('holiday_calendars')->where('tenant_id',$this->tid());
        return is_numeric($value)?$q->where('id',(int)$value)->firstOrFail():$q->where('uuid',(string)$value)->firstOrFail();
    }
    private function holidayRow(string $uuid): object
    {
        return DB::table('holidays as h')->leftJoin('holiday_calendars as c','c.id','=','h.holiday_calendar_id')->leftJoin('tenant_lookups as t','t.id','=','h.type_id')->leftJoin('tenant_lookups as cat','cat.id','=','h.category_id')->where('h.tenant_id',$this->tid())->where('h.uuid',$uuid)->whereNull('h.deleted_at')->select('h.*','c.uuid as calendar_uuid','c.name as calendar_name','t.name as type_name','cat.name as category_name')->firstOrFail();
    }
    private function holidayPayload(object $row): array { return (array)$row; }
    private function holidayBundle(object $row): array { return ['holiday'=>$this->holidayPayload($row),'applicabilities'=>DB::table('holiday_applicabilities')->where('tenant_id',$this->tid())->where('holiday_id',$row->id)->get()->all()]; }
    private function lookupId(mixed $value): ?int
    {
        if($value===null||$value==='')return null;
        $q=DB::table('tenant_lookups')->where(fn($x)=>$x->whereNull('tenant_id')->orWhere('tenant_id',$this->tid()));
        return (int)(is_numeric($value)?$q->where('id',(int)$value)->value('id'):$q->where('uuid',(string)$value)->value('id'));
    }
    private function syncHolidayApplicabilities(int $holidayId,array $items): void
    {
        DB::table('holiday_applicabilities')->where('tenant_id',$this->tid())->where('holiday_id',$holidayId)->delete();
        foreach($items as $item)if(isset($item['applicable_type'],$item['applicable_id']))DB::table('holiday_applicabilities')->insert(['tenant_id'=>$this->tid(),'holiday_id'=>$holidayId,'applicable_type'=>$item['applicable_type'],'applicable_id'=>$item['applicable_id'],'created_at'=>now()]);
    }
    public function holidays(Request $request): mixed
    {
        $q=DB::table('holidays as h')->leftJoin('holiday_calendars as c','c.id','=','h.holiday_calendar_id')->where('h.tenant_id',$this->tid())->whereNull('h.deleted_at')->select('h.*','c.uuid as calendar_uuid','c.name as calendar_name');
        if($request->filled('search')){$term='%'.$request->input('search').'%';$q->where('h.name','like',$term);}
        $page=$q->latest('h.id')->paginate((int)$request->input('per_page',25));
        return $this->success(collect($page->items())->map(fn($r)=>$this->holidayPayload($r))->all(),'Holidays fetched.',200,['current_page'=>$page->currentPage(),'per_page'=>$page->perPage(),'total'=>$page->total(),'last_page'=>$page->lastPage()]);
    }
    public function storeHoliday(Request $request): mixed
    {
        $d=$request->validate(['holiday_calendar_id'=>'required','name'=>'required|string|max:255','type_id'=>'nullable','category_id'=>'nullable','holiday_date'=>'required|date','start_date'=>'nullable|date','end_date'=>'nullable|date|after_or_equal:start_date','total_days'=>'nullable|numeric|min:0.5','is_half_day'=>'nullable|boolean','half_day_session'=>'nullable|string','recurring_yearly'=>'nullable|boolean','optional_holiday'=>'nullable|boolean','applicable_to_all'=>'nullable|boolean','description'=>'nullable|string','color'=>'nullable|string|max:30','applicabilities'=>'nullable|array']);
        $cal=$this->holidayCalendar($d['holiday_calendar_id']);$data=collect($d)->except(['holiday_calendar_id','applicabilities'])->all();$data+=['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tid(),'holiday_calendar_id'=>$cal->id,'type_id'=>$this->lookupId($d['type_id']??null),'category_id'=>$this->lookupId($d['category_id']??null),'created_by'=>request()->user()?->id,'updated_by'=>request()->user()?->id,'created_at'=>now(),'updated_at'=>now()];
        $id=DB::table('holidays')->insertGetId($data);$this->syncHolidayApplicabilities($id,$d['applicabilities']??[]);return $this->success(['holiday'=>$this->holidayBundle($this->holidayRow($data['uuid']))],'Holiday created.',201);
    }
    public function showHoliday(string $holiday_uuid): mixed { return $this->success(['holiday'=>$this->holidayBundle($this->holidayRow($holiday_uuid))],'Holiday fetched.'); }
    public function updateHoliday(Request $request,string $holiday_uuid): mixed
    {
        $row=$this->holidayRow($holiday_uuid);$d=$request->validate(['holiday_calendar_id'=>'sometimes','name'=>'sometimes|string|max:255','type_id'=>'nullable','category_id'=>'nullable','holiday_date'=>'sometimes|date','start_date'=>'sometimes|nullable|date','end_date'=>'sometimes|nullable|date|after_or_equal:start_date','total_days'=>'sometimes|numeric|min:0.5','is_half_day'=>'sometimes|boolean','half_day_session'=>'sometimes|nullable|string','recurring_yearly'=>'sometimes|boolean','optional_holiday'=>'sometimes|boolean','applicable_to_all'=>'sometimes|boolean','description'=>'sometimes|nullable|string','color'=>'sometimes|nullable|string|max:30','applicabilities'=>'sometimes|array']);$data=collect($d)->except(['holiday_calendar_id','type_id','category_id','applicabilities'])->all();if(array_key_exists('holiday_calendar_id',$d))$data['holiday_calendar_id']=$this->holidayCalendar($d['holiday_calendar_id'])->id;if(array_key_exists('type_id',$d))$data['type_id']=$this->lookupId($d['type_id']);if(array_key_exists('category_id',$d))$data['category_id']=$this->lookupId($d['category_id']);$data['updated_by']=request()->user()?->id;$data['updated_at']=now();DB::table('holidays')->where('id',$row->id)->update($data);if(array_key_exists('applicabilities',$d))$this->syncHolidayApplicabilities($row->id,$d['applicabilities']);return $this->success(['holiday'=>$this->holidayBundle($this->holidayRow($holiday_uuid))],'Holiday updated.');
    }
    public function deleteHoliday(string $holiday_uuid): mixed { $row=$this->holidayRow($holiday_uuid);DB::table('holidays')->where('id',$row->id)->update(['deleted_at'=>now(),'updated_at'=>now()]);return $this->success(null,'Holiday deleted.'); }
    public function duplicateHoliday(string $holiday_uuid): mixed
    {
        $source=$this->holidayRow($holiday_uuid);$dates=['holiday_date'=>Carbon::parse($source->holiday_date)->addYear(),'start_date'=>$source->start_date?Carbon::parse($source->start_date)->addYear():null,'end_date'=>$source->end_date?Carbon::parse($source->end_date)->addYear():null];$id=DB::table('holidays')->insertGetId(['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tid(),'holiday_calendar_id'=>$source->holiday_calendar_id,'name'=>$source->name.' '.(Carbon::parse($source->holiday_date)->year+1),'type_id'=>$source->type_id,'category_id'=>$source->category_id,'holiday_date'=>$dates['holiday_date'],'start_date'=>$dates['start_date'],'end_date'=>$dates['end_date'],'total_days'=>$source->total_days,'is_half_day'=>$source->is_half_day,'half_day_session'=>$source->half_day_session,'recurring_yearly'=>$source->recurring_yearly,'optional_holiday'=>$source->optional_holiday,'applicable_to_all'=>$source->applicable_to_all,'description'=>$source->description,'color'=>$source->color,'created_by'=>request()->user()?->id,'updated_by'=>request()->user()?->id,'created_at'=>now(),'updated_at'=>now()]);foreach(DB::table('holiday_applicabilities')->where('tenant_id',$this->tid())->where('holiday_id',$source->id)->get() as $a)DB::table('holiday_applicabilities')->insert(['tenant_id'=>$this->tid(),'holiday_id'=>$id,'applicable_type'=>$a->applicable_type,'applicable_id'=>$a->applicable_id,'created_at'=>now()]);$new=DB::table('holidays')->where('id',$id)->first();return $this->success(['holiday'=>$this->holidayBundle($this->holidayRow($new->uuid))],'Holiday duplicated.',201);
    }
    private function queueHolidayJob(Request $request,string $type): mixed { $id=DB::table('tenant_import_export_jobs')->insertGetId(['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tid(),'user_id'=>$request->user()?->id,'type'=>$type,'module'=>'holidays','status'=>'queued','payload'=>json_encode($request->all()),'created_at'=>now(),'updated_at'=>now()]);return $this->success(['job'=>DB::table('tenant_import_export_jobs')->where('id',$id)->first()],'Holiday '.$type.' queued.',202); }
    public function importHolidays(Request $request): mixed { return $this->queueHolidayJob($request,'import'); }
    public function exportHolidays(Request $request): mixed { return $this->queueHolidayJob($request,'export'); }
    public function holidayCalendars(): mixed { return $this->success(['calendars'=>DB::table('holiday_calendars')->where('tenant_id',$this->tid())->latest('id')->get()],'Holiday calendars fetched.'); }
    public function storeHolidayCalendar(Request $request): mixed { $d=$request->validate(['name'=>'required|string|max:255','description'=>'nullable|string','country_id'=>'nullable|integer','state_id'=>'nullable|integer','is_default'=>'nullable|boolean','status'=>'nullable|string']);if(($d['is_default']??false))DB::table('holiday_calendars')->where('tenant_id',$this->tid())->update(['is_default'=>false]);$id=DB::table('holiday_calendars')->insertGetId($d+['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tid(),'created_by'=>request()->user()?->id,'updated_by'=>request()->user()?->id,'status'=>$d['status']??'active','created_at'=>now(),'updated_at'=>now()]);return $this->success(['calendar'=>DB::table('holiday_calendars')->find($id)],'Holiday calendar created.',201); }
    public function showHolidayCalendar(string $calendar_uuid): mixed { $c=$this->holidayCalendar($calendar_uuid);return $this->success(['calendar'=>$c,'holidays'=>DB::table('holidays')->where('tenant_id',$this->tid())->where('holiday_calendar_id',$c->id)->whereNull('deleted_at')->get()],'Holiday calendar fetched.'); }
    public function updateHolidayCalendar(Request $request,string $calendar_uuid): mixed { $c=$this->holidayCalendar($calendar_uuid);$d=$request->validate(['name'=>'sometimes|string|max:255','description'=>'sometimes|nullable|string','country_id'=>'sometimes|nullable|integer','state_id'=>'sometimes|nullable|integer','is_default'=>'sometimes|boolean','status'=>'sometimes|string']);if(($d['is_default']??false))DB::table('holiday_calendars')->where('tenant_id',$this->tid())->update(['is_default'=>false]);DB::table('holiday_calendars')->where('id',$c->id)->update($d+['updated_by'=>request()->user()?->id,'updated_at'=>now()]);return $this->success(['calendar'=>DB::table('holiday_calendars')->where('id',$c->id)->first()],'Holiday calendar updated.'); }
    public function deleteHolidayCalendar(string $calendar_uuid): mixed { $c=$this->holidayCalendar($calendar_uuid);DB::table('holiday_calendars')->where('id',$c->id)->delete();return $this->success(null,'Holiday calendar deleted.'); }
    public function holidayGroups(): mixed { return $this->success(['groups'=>DB::table('holiday_groups')->where('tenant_id',$this->tid())->latest('id')->get()],'Holiday groups fetched.'); }
    public function storeHolidayGroup(Request $request): mixed { $d=$request->validate(['name'=>'required|string|max:255','description'=>'nullable|string','status'=>'nullable|string']);$id=DB::table('holiday_groups')->insertGetId($d+['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tid(),'status'=>$d['status']??'active','created_at'=>now(),'updated_at'=>now()]);return $this->success(['group'=>DB::table('holiday_groups')->find($id)],'Holiday group created.',201); }
    public function updateHolidayGroup(Request $request,string $group_uuid): mixed { $g=DB::table('holiday_groups')->where('tenant_id',$this->tid())->where('uuid',$group_uuid)->firstOrFail();$d=$request->validate(['name'=>'sometimes|string|max:255','description'=>'sometimes|nullable|string','status'=>'sometimes|string']);DB::table('holiday_groups')->where('id',$g->id)->update($d+['updated_at'=>now()]);return $this->success(['group'=>DB::table('holiday_groups')->find($g->id)],'Holiday group updated.'); }
    public function groupMembers(string $group_uuid): mixed { $g=DB::table('holiday_groups')->where('tenant_id',$this->tid())->where('uuid',$group_uuid)->firstOrFail();$members=DB::table('holiday_group_members as m')->join('staff as s','s.id','=','m.staff_id')->where('m.tenant_id',$this->tid())->where('m.holiday_group_id',$g->id)->select('m.id','s.uuid as staff_uuid','s.display_name as staff_name','s.employee_code','m.assigned_at')->get();return $this->success(['members'=>$members],'Holiday group members fetched.'); }
    public function addGroupMembers(Request $request,string $group_uuid): mixed { $g=DB::table('holiday_groups')->where('tenant_id',$this->tid())->where('uuid',$group_uuid)->firstOrFail();$ids=$request->input('staff_ids');if($ids===null)$ids=[$request->input('staff_id')];foreach((array)$ids as $value){$staff=$this->staff($value);DB::table('holiday_group_members')->updateOrInsert(['tenant_id'=>$this->tid(),'holiday_group_id'=>$g->id,'staff_id'=>$staff->id],['assigned_at'=>now()]);}return $this->groupMembers($group_uuid); }
    public function removeGroupMember(string $group_uuid,string $staff_uuid): mixed { $g=DB::table('holiday_groups')->where('tenant_id',$this->tid())->where('uuid',$group_uuid)->firstOrFail();$staff=$this->staff($staff_uuid);DB::table('holiday_group_members')->where('tenant_id',$this->tid())->where('holiday_group_id',$g->id)->where('staff_id',$staff->id)->delete();return $this->success(null,'Holiday group member removed.'); }

}
