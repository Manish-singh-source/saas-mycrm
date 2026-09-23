<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Shared\BaseApiController;
use App\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TenantDashboardController extends BaseApiController
{
    public function __construct(private readonly TenantContext $tenant) {}
    private function tid(): int { return $this->tenant->id(); }

    private function dates(Request $request): array
    {
        $data=$request->validate(['date_from'=>'nullable|date','date_to'=>'nullable|date|after_or_equal:date_from']);
        return [$data['date_from']??null,$data['date_to']??null];
    }
    private function dateRange($query,string $column,?string $from,?string $to): void
    {
        if($from)$query->whereDate($column,'>=',$from);
        if($to)$query->whereDate($column,'<=',$to);
    }
    private function job(Request $request,string $module): mixed
    {
        $id=DB::table('tenant_import_export_jobs')->insertGetId(['uuid'=>(string)Str::uuid(),'tenant_id'=>$this->tid(),'user_id'=>$request->user()?->id,'type'=>'export','module'=>$module,'status'=>'queued','payload'=>json_encode($request->all()),'created_at'=>now(),'updated_at'=>now()]);
        return $this->success(['job'=>DB::table('tenant_import_export_jobs')->where('id',$id)->first()],'Export queued.',202);
    }
    private function statusQuery(string $table,array $codes,bool $exclude=false)
    {
        $query=DB::table($table.' as records')
            ->leftJoin('tenant_lookups as status_lookup','status_lookup.id','=','records.status_id')
            ->where('records.tenant_id',$this->tid());

        return $query->where(function($statusQuery)use($codes,$exclude){
            $statusQuery->whereNull('status_lookup.code');
            if($exclude)$statusQuery->orWhereNotIn('status_lookup.code',$codes);
            else$statusQuery->orWhereIn('status_lookup.code',$codes);
        });
    }
    public function navigation(): mixed
    {
        $subscription=DB::table('subscriptions')->where('tenant_id',$this->tid())->latest('id')->first();
        $overdueTasks=$this->statusQuery('tasks',['completed','cancelled'],true)
            ->whereNull('records.deleted_at')
            ->whereNull('records.completed_at')
            ->whereNotNull('records.due_at')
            ->where('records.due_at','<',now())
            ->count();
        $openIssues=$this->statusQuery('client_issues',['open','in_progress'])
            ->whereNull('records.deleted_at')
            ->whereNull('records.resolved_at')
            ->whereNull('records.closed_at')
            ->count();
        $pendingLeave=$this->statusQuery('leave_requests',['pending'])->count();
        $renewalsDueSoon=$this->statusQuery('renewals',['cancelled','completed'],true)
            ->whereNull('records.deleted_at')
            ->whereBetween('records.renewal_date',[today(),today()->addDays(30)])
            ->count();

        return $this->success(['navigation'=>['modules'=>[],'subscription'=>$subscription,'badges'=>['overdue_tasks'=>$overdueTasks,'open_issues'=>$openIssues,'pending_leave'=>$pendingLeave,'unread_notifications'=>0,'renewals_due_soon'=>$renewalsDueSoon]]],'Navigation fetched.');
    }
    public function summary(Request $request): mixed
    {
        [$from,$to]=$this->dates($request);$count=function(string $table,string $date='created_at')use($from,$to){$q=DB::table($table)->where('tenant_id',$this->tid());$this->dateRange($q,$date,$from,$to);return $q->count();};
        $staff=DB::table('staff')->where('tenant_id',$this->tid())->where('employment_status','active')->count();$present=DB::table('attendance_records')->where('tenant_id',$this->tid())->whereDate('attendance_date',today())->whereNotNull('check_in_at')->count();
        return $this->success(['summary'=>['leads'=>$count('parties'),'clients'=>$count('parties'),'vendors'=>$count('parties'),'active_projects'=>$count('projects'),'open_tasks'=>DB::table('tasks')->where('tenant_id',$this->tid())->whereNotIn('status',['completed','cancelled'])->count(),'open_support_issues'=>DB::table('client_issues')->where('tenant_id',$this->tid())->whereIn('status',['open','in_progress'])->count(),'staff_count'=>$staff,'present_today'=>$present,'absent_today'=>max(0,$staff-$present),'pending_leave_approvals'=>DB::table('leave_requests')->where('tenant_id',$this->tid())->where('status','pending')->count()]],'Dashboard summary fetched.');
    }
    public function chart(Request $request,string $chart): mixed
    {
        [$from,$to]=$this->dates($request);$q=null;
        if($chart==='leads-pipeline')$q=DB::table('parties')->where('tenant_id',$this->tid())->where('party_type','lead')->select('stage_id as label',DB::raw('COUNT(*) as total'))->groupBy('stage_id');
        elseif($chart==='projects')$q=DB::table('projects')->where('tenant_id',$this->tid())->select('status as label',DB::raw('COUNT(*) as total'))->groupBy('status');
        elseif($chart==='tasks')$q=DB::table('tasks')->where('tenant_id',$this->tid())->select('status as label',DB::raw('COUNT(*) as total'))->groupBy('status');
        elseif($chart==='attendance')$q=DB::table('attendance_records')->where('tenant_id',$this->tid())->select('attendance_date as label',DB::raw('COUNT(*) as total'))->groupBy('attendance_date');
        elseif($chart==='support')$q=DB::table('client_issues')->where('tenant_id',$this->tid())->select('status as label',DB::raw('COUNT(*) as total'))->groupBy('status');
        elseif(in_array($chart,['revenue','payment-success-failure-trend'],true))$q=DB::table('tenant_payments')->where('tenant_id',$this->tid())->where('status','paid')->selectRaw("DATE_FORMAT(paid_at,'%Y-%m') as label, SUM(amount) as total")->groupByRaw("DATE_FORMAT(paid_at,'%Y-%m')");
        else return $this->businessError('Unknown dashboard chart.','UNKNOWN_CHART',404);
        $column=$chart==='attendance'?'attendance_date':($chart==='revenue'||$chart==='payment-success-failure-trend'?'paid_at':'created_at');$this->dateRange($q,$column,$from,$to);
        return $this->success(['chart'=>['code'=>$chart,'series'=>$q->orderBy('label')->get()]],'Dashboard chart fetched.');
    }
    public function recentActivities(Request $request): mixed
    {
        [$from,$to]=$this->dates($request);$q=DB::table('activity_logs as a')->leftJoin('users as u','u.id','=','a.actor_user_id')->where('a.tenant_id',$this->tid())->select('a.id as uuid','a.event','a.description','a.created_at','u.uuid as actor_uuid','u.display_name as actor_name');$this->dateRange($q,'a.created_at',$from,$to);
        return $this->success(['activities'=>$q->latest('a.created_at')->limit(5)->get()],'Recent activities fetched.');
    }
    public function widgets(): mixed
    {
        $row=DB::table('user_preferences')->where('tenant_id',$this->tid())->where('user_id',request()->user()?->id)->where('group','dashboard')->where('key','widgets')->first();
        return $this->success(['widgets'=>$row?json_decode($row->value,true):[['code'=>'my_tasks','position'=>1,'visible'=>true,'settings'=>['limit'=>5]],['code'=>'calendar','position'=>2,'visible'=>true],['code'=>'notifications','position'=>3,'visible'=>true]]],'Dashboard widgets fetched.');
    }
    public function updateWidgets(Request $request): mixed
    {
        $d=$request->validate(['widgets'=>'required|array','widgets.*.code'=>'required|string','widgets.*.position'=>'required|integer|min:1','widgets.*.visible'=>'nullable|boolean','widgets.*.settings'=>'nullable|array']);$positions=collect($d['widgets'])->pluck('position');if($positions->count()!==$positions->unique()->count())return $this->businessError('Widget positions must be unique.','DUPLICATE_WIDGET_POSITION',422);
        DB::table('user_preferences')->updateOrInsert(['tenant_id'=>$this->tid(),'user_id'=>request()->user()?->id,'group'=>'dashboard','key'=>'widgets'],['value'=>json_encode($d['widgets']),'updated_at'=>now(),'created_at'=>now()]);
        return $this->success(['widgets'=>$d['widgets']],'Dashboard widgets saved.');
    }
    public function exportDashboard(Request $request): mixed { $request->validate(['format'=>'nullable|in:csv,xlsx,pdf','date_from'=>'nullable|date','date_to'=>'nullable|date|after_or_equal:date_from','sections'=>'nullable|array']);return $this->job($request,'dashboard'); }
    public function widget(Request $request,string $widget): mixed
    {
        [$from,$to]=$this->dates($request);$limit=min(50,(int)$request->input('per_page',5));$data=null;$key=str_replace('-','_',$widget);
        if($widget==='my-tasks')$data=DB::table('tasks')->where('tenant_id',$this->tid())->whereNotIn('status',['completed','cancelled'])->latest('id')->limit($limit)->get(['uuid','title','status','priority','due_at as due_date']);
        elseif($widget==='upcoming-events')$data=DB::table('calendar_events')->where('tenant_id',$this->tid())->where('starts_at','>=',now())->orderBy('starts_at')->limit($limit)->get(['uuid','title','starts_at','ends_at']);
        elseif($widget==='recent-leads')$data=DB::table('parties')->where('tenant_id',$this->tid())->where('party_type','lead')->latest('id')->limit($limit)->get(['uuid','party_number as lead_number','stage_id','created_at']);
        elseif($widget==='overdue-invoices')$data=DB::table('tenant_invoices')->where('tenant_id',$this->tid())->whereNull('deleted_at')->where('due_date','<',today())->where('balance_amount','>',0)->latest('id')->limit($limit)->get(['uuid','invoice_number','due_date','balance_amount']);
        elseif($widget==='recent-activities')return $this->recentActivities($request);
        else return $this->businessError('Unknown dashboard widget.','UNKNOWN_WIDGET',404);
        return $this->success([$key=>$data],'Dashboard widget fetched.');
    }
    private function reportDefinitions(): array { return ['crm-summary'=>'CRM Summary','hr-summary'=>'HR Summary','payroll-summary'=>'Payroll Summary','renewal-summary'=>'Renewal Summary','finance-summary'=>'Finance Summary','project-summary'=>'Project Summary','task-summary'=>'Task Summary','support-summary'=>'Support Summary']; }
    public function reportsDashboard(): mixed
    {
        $defs=$this->reportDefinitions();return $this->success(['dashboard'=>['cards'=>['crm_clients'=>DB::table('parties')->where('tenant_id',$this->tid())->where('party_type','client')->count(),'crm_leads'=>DB::table('parties')->where('tenant_id',$this->tid())->where('party_type','lead')->count(),'staff'=>DB::table('staff')->where('tenant_id',$this->tid())->count(),'open_projects'=>DB::table('projects')->where('tenant_id',$this->tid())->whereNotIn('status',['completed','cancelled'])->count(),'invoice_balance'=>DB::table('tenant_invoices')->where('tenant_id',$this->tid())->whereNull('deleted_at')->sum('balance_amount')],'available_reports'=>collect($defs)->map(fn($name,$code)=>['code'=>$code,'name'=>$name])->values(),'recent_exports'=>DB::table('tenant_import_export_jobs')->where('tenant_id',$this->tid())->where('module','like','report_%')->latest('id')->limit(10)->get()]],'Reports dashboard fetched.');
    }
    public function report(Request $request,string $code): mixed
    {
        $defs=$this->reportDefinitions();if(!isset($defs[$code]))return $this->businessError('Unknown report code.','UNKNOWN_REPORT',404);[$from,$to]=$this->dates($request);$rows=[];
        if($code==='crm-summary')$rows=DB::table('parties')->where('tenant_id',$this->tid())->select('party_type as type',DB::raw('COUNT(*) as total'))->groupBy('party_type')->get();
        elseif($code==='hr-summary')$rows=DB::table('staff')->where('tenant_id',$this->tid())->select('employment_status as status',DB::raw('COUNT(*) as total'))->groupBy('employment_status')->get();
        elseif($code==='payroll-summary')$rows=DB::table('payrolls')->where('tenant_id',$this->tid())->select('payment_status as status',DB::raw('SUM(net_salary) as total'))->groupBy('payment_status')->get();
        elseif($code==='renewal-summary')$rows=DB::table('renewals')->where('tenant_id',$this->tid())->select('status',DB::raw('COUNT(*) as total'))->groupBy('status')->get();
        elseif($code==='finance-summary')$rows=DB::table('tenant_invoices')->where('tenant_id',$this->tid())->whereNull('deleted_at')->select('status',DB::raw('SUM(total_amount) as amount'),DB::raw('SUM(balance_amount) as balance'),DB::raw('COUNT(*) as total'))->groupBy('status')->get();
        elseif($code==='project-summary')$rows=DB::table('projects')->where('tenant_id',$this->tid())->select('status',DB::raw('COUNT(*) as total'))->groupBy('status')->get();
        elseif($code==='task-summary')$rows=DB::table('tasks')->where('tenant_id',$this->tid())->select('status',DB::raw('COUNT(*) as total'))->groupBy('status')->get();
        elseif($code==='support-summary')$rows=DB::table('client_issues')->where('tenant_id',$this->tid())->select('status',DB::raw('COUNT(*) as total'))->groupBy('status')->get();
        return $this->success(['report'=>['code'=>$code,'name'=>$defs[$code]],'rows'=>$rows,'filters'=>['date_from'=>$from,'date_to'=>$to]],'Report fetched.');
    }
    public function customReports(): mixed { return $this->success(['custom_reports'=>[],'placeholder'=>true,'message'=>'Custom report storage is not configured yet.'],'Custom reports fetched.'); }
    public function createCustomReport(Request $request): mixed { $d=$request->validate(['name'=>'required|string|max:255','module'=>'required|string','filters'=>'nullable|array','columns'=>'nullable|array']);return $this->success(['placeholder'=>true,'payload'=>$d],'Custom report storage is not configured yet.',202); }
    public function runCustomReport(string $report_uuid): mixed { return $this->success(['rows'=>[],'placeholder'=>true],'Custom report storage is not configured yet.'); }
    public function exportReport(Request $request,string $code): mixed { if(!isset($this->reportDefinitions()[$code]))return $this->businessError('Unknown report code.','UNKNOWN_REPORT',404);$request->validate(['format'=>'nullable|in:csv,xlsx,pdf','date_from'=>'nullable|date','date_to'=>'nullable|date|after_or_equal:date_from','filters'=>'nullable|array']);return $this->job($request,'report_'.$code); }
}
