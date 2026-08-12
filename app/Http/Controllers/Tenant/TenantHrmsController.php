<?php

namespace App\Http\Controllers\Tenant;

use App\Services\Tenant\TenantWorkspaceService;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TenantHrmsController extends BaseTenantController
{
    public function __construct(private readonly TenantWorkspaceService $tenant) {}

    public function attendanceDashboard(): JsonResponse
    {
        return $this->success(['dashboard' => [
            'cards' => [
                'present_today' => $this->attendanceRows()->whereDate('attendance_records.attendance_date', today())->whereNotNull('attendance_records.check_in_at')->count(),
                'missing_check_out' => $this->base('attendance_records')->whereDate('attendance_date', today())->whereNotNull('check_in_at')->whereNull('check_out_at')->count(),
                'pending_corrections' => $this->base('attendance_requests')->where('status', 'pending')->count(),
                'late_records_this_month' => $this->base('attendance_records')->whereMonth('attendance_date', today()->month)->whereYear('attendance_date', today()->year)->where('total_minutes', '<', 480)->count(),
            ],
            'daily' => $this->attendanceRows()->whereDate('attendance_records.attendance_date', today())->orderBy('staff.display_name')->limit(20)->get(),
            'pending_requests' => $this->attendanceRequestRows()->where('attendance_requests.status', 'pending')->orderByDesc('attendance_requests.id')->limit(20)->get(),
            'department_summary' => $this->attendanceRows()
                ->leftJoin('departments', 'departments.id', '=', 'staff.department_id')
                ->whereDate('attendance_records.attendance_date', today())
                ->groupBy('departments.name')
                ->selectRaw('coalesce(departments.name, ?) as department, count(*) as total', ['Unassigned'])
                ->get(),
        ]]);
    }

    public function attendanceDaily(Request $request): JsonResponse
    {
        $date = $request->input('date', today()->toDateString());
        $page = $this->filterSearch($this->attendanceRows()->whereDate('attendance_records.attendance_date', $date), $request, ['staff.display_name', 'staff.employee_code'])
            ->orderBy('staff.display_name')
            ->paginate($request->integer('per_page', 25));

        return $this->list($page->items(), $page);
    }

    public function attendanceMonthly(Request $request): JsonResponse
    {
        $month = (int) $request->integer('month', today()->month);
        $year = (int) $request->integer('year', today()->year);
        $rows = $this->attendanceRows()->whereMonth('attendance_records.attendance_date', $month)->whereYear('attendance_records.attendance_date', $year)->orderBy('staff.display_name')->get();
        $grid = $rows->groupBy('staff_uuid')->map(fn ($items) => [
            'staff_uuid' => $items->first()->staff_uuid,
            'staff_name' => $items->first()->staff_name,
            'employee_code' => $items->first()->employee_code,
            'days' => $items->mapWithKeys(fn ($row) => [substr((string) $row->attendance_date, -2) => $row])->all(),
            'present_days' => $items->whereNotNull('check_in_at')->count(),
            'total_minutes' => $items->sum('total_minutes'),
        ])->values();

        return $this->success(['month' => $month, 'year' => $year, 'grid' => $grid, 'records' => $rows]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $staffId = $this->staffIdForUser($request);
        $date = today()->toDateString();
        $existing = $this->base('attendance_records')->where('staff_id', $staffId)->whereDate('attendance_date', $date)->first();
        if ($existing) {
            DB::table('attendance_records')->where('id', $existing->id)->update(['check_in_at' => $existing->check_in_at ?: now(), 'updated_at' => now()]);
            return $this->success(['attendance' => $this->attendanceRows()->where('attendance_records.id', $existing->id)->first()], 'Checked in.');
        }

        $id = DB::table('attendance_records')->insertGetId(['tenant_id' => $this->tenantId(), 'staff_id' => $staffId, 'attendance_date' => $date, 'check_in_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        return $this->success(['attendance' => $this->attendanceRows()->where('attendance_records.id', $id)->first()], 'Checked in.', 201);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $staffId = $this->staffIdForUser($request);
        $record = $this->base('attendance_records')->where('staff_id', $staffId)->whereDate('attendance_date', today())->first() ?: abort(404, 'Check-in record not found.');
        $minutes = $record->check_in_at ? now()->diffInMinutes($record->check_in_at) : (int) $record->total_minutes;
        DB::table('attendance_records')->where('id', $record->id)->update(['check_out_at' => now(), 'total_minutes' => $minutes, 'updated_at' => now()]);

        return $this->success(['attendance' => $this->attendanceRows()->where('attendance_records.id', $record->id)->first()], 'Checked out.');
    }

    public function storeAttendanceRecord(Request $request): JsonResponse
    {
        $payload = $this->attendancePayload($request);
        $id = DB::table('attendance_records')->insertGetId([...$payload, 'tenant_id' => $this->tenantId(), 'created_at' => now(), 'updated_at' => now()]);

        return $this->success(['attendance' => $this->attendanceRows()->where('attendance_records.id', $id)->first()], 'Attendance marked.', 201);
    }

    public function showAttendanceRecord(int $record_id): JsonResponse
    {
        return $this->success(['attendance' => $this->attendanceRows()->where('attendance_records.id', $record_id)->first() ?: abort(404)]);
    }

    public function updateAttendanceRecord(Request $request, int $record_id): JsonResponse
    {
        $this->scoped('attendance_records', $record_id);
        DB::table('attendance_records')->where('id', $record_id)->update([...$this->attendancePayload($request, true), 'updated_at' => now()]);

        return $this->success(['attendance' => $this->attendanceRows()->where('attendance_records.id', $record_id)->first()], 'Attendance updated.');
    }

    public function importAttendance(Request $request): JsonResponse { return $this->queued($request, 'import', 'attendance'); }
    public function exportAttendance(Request $request): JsonResponse { return $this->queued($request, 'export', 'attendance'); }

    public function attendanceRequests(Request $request): JsonResponse
    {
        $page = $this->filterSearch($this->attendanceRequestRows(), $request, ['staff.display_name', 'attendance_requests.request_type', 'attendance_requests.reason'])
            ->orderByDesc('attendance_requests.id')
            ->paginate($request->integer('per_page', 25));

        return $this->list($page->items(), $page);
    }

    public function storeAttendanceRequest(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'staff_id' => ['nullable'],
            'attendance_record_id' => ['nullable', 'integer'],
            'request_date' => ['nullable', 'date'],
            'request_type' => ['required', 'string', 'max:80'],
            'reason' => ['nullable', 'string'],
        ]);
        $staffId = $this->staffInputToId($payload['staff_id'] ?? null) ?? $this->staffIdForUser($request);
        $id = DB::table('attendance_requests')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'staff_id' => $staffId,
            'request_date' => $payload['request_date'] ?? today()->toDateString(),
            'request_type' => $payload['request_type'],
            'reason' => $payload['reason'] ?? null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['request' => $this->attendanceRequestRows()->where('attendance_requests.id', $id)->first()], 'Correction requested.', 201);
    }

    public function showAttendanceRequest(string $request_uuid): JsonResponse
    {
        return $this->success(['request' => $this->attendanceRequestRows()->where('attendance_requests.uuid', $request_uuid)->first() ?: abort(404)]);
    }

    public function approveAttendanceRequest(Request $request, string $request_uuid): JsonResponse
    {
        return $this->attendanceRequestAction($request, $request_uuid, 'approved');
    }

    public function rejectAttendanceRequest(Request $request, string $request_uuid): JsonResponse
    {
        return $this->attendanceRequestAction($request, $request_uuid, 'rejected');
    }

    public function leaveDashboard(): JsonResponse
    {
        return $this->success(['dashboard' => [
            'cards' => [
                'pending_requests' => $this->leaveRequestRows()->whereNull('leave_requests.approved_at')->count(),
                'approved_this_month' => $this->base('leave_requests')->whereMonth('approved_at', today()->month)->whereYear('approved_at', today()->year)->count(),
                'leave_types' => $this->base('leave_types')->count(),
                'low_balances' => $this->base('leave_balances')->where('remaining', '<=', 2)->count(),
            ],
            'pending_requests' => $this->leaveRequestRows()->whereNull('leave_requests.approved_at')->orderByDesc('leave_requests.id')->limit(20)->get(),
            'balances' => $this->leaveBalanceRows()->orderBy('staff.display_name')->limit(20)->get(),
            'calendar' => $this->leaveRequestRows()->whereDate('leave_requests.end_date', '>=', today())->orderBy('leave_requests.start_date')->limit(20)->get(),
        ]]);
    }

    public function leaveRequests(Request $request): JsonResponse
    {
        $page = $this->filterSearch($this->leaveRequestRows(), $request, ['staff.display_name', 'leave_types.name', 'leave_requests.reason'])
            ->orderByDesc('leave_requests.id')
            ->paginate($request->integer('per_page', 25));

        return $this->list($page->items(), $page);
    }

    public function storeLeaveRequest(Request $request): JsonResponse
    {
        $payload = $this->leaveRequestPayload($request);
        $overlap = $this->base('leave_requests')->where('staff_id', $payload['staff_id'])->whereDate('start_date', '<=', $payload['end_date'])->whereDate('end_date', '>=', $payload['start_date'])->exists();
        if ($overlap) {
            return $this->businessError('Leave dates overlap with an existing request.', 'LEAVE_OVERLAP', 409);
        }
        $id = DB::table('leave_requests')->insertGetId([...$payload, 'tenant_id' => $this->tenantId(), 'created_at' => now(), 'updated_at' => now()]);

        return $this->success(['request' => $this->leaveRequestRows()->where('leave_requests.id', $id)->first()], 'Leave requested.', 201);
    }

    public function showLeaveRequest(int $request_id): JsonResponse { return $this->success(['request' => $this->leaveRequestRows()->where('leave_requests.id', $request_id)->first() ?: abort(404)]); }
    public function approveLeaveRequest(Request $request, int $request_id): JsonResponse { return $this->leaveRequestAction($request, $request_id, 'approved'); }
    public function rejectLeaveRequest(Request $request, int $request_id): JsonResponse { return $this->leaveRequestAction($request, $request_id, 'rejected'); }
    public function cancelLeaveRequest(Request $request, int $request_id): JsonResponse { return $this->leaveRequestAction($request, $request_id, 'cancelled'); }

    public function leaveBalances(Request $request): JsonResponse
    {
        $page = $this->filterSearch($this->leaveBalanceRows(), $request, ['staff.display_name', 'leave_types.name'])->orderBy('staff.display_name')->paginate($request->integer('per_page', 25));

        return $this->list($page->items(), $page);
    }

    public function adjustLeaveBalance(Request $request): JsonResponse
    {
        $payload = $request->validate(['staff_id' => ['required'], 'leave_type_id' => ['required'], 'year' => ['nullable', 'integer'], 'remaining' => ['required', 'numeric'], 'remarks' => ['nullable', 'string']]);
        $staffId = $this->staffInputToId($payload['staff_id']);
        $typeId = $this->idFrom('leave_types', $payload['leave_type_id']);
        DB::table('leave_balances')->updateOrInsert(
            ['tenant_id' => $this->tenantId(), 'staff_id' => $staffId, 'leave_type_id' => $typeId, 'year' => $payload['year'] ?? today()->year],
            ['remaining' => $payload['remaining']]
        );

        return $this->success(['balances' => $this->leaveBalanceRows()->where('leave_balances.staff_id', $staffId)->get()], 'Leave balance adjusted.');
    }

    public function leaveCalendar(): JsonResponse
    {
        return $this->success(['events' => $this->leaveRequestRows()->orderBy('leave_requests.start_date')->get()]);
    }

    public function leaveTypes(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            $id = DB::table('leave_types')->insertGetId(['tenant_id' => $this->tenantId(), ...$request->validate(['name' => ['required', 'string'], 'code' => ['required', 'string'], 'paid' => ['nullable', 'boolean'], 'carry_forward' => ['nullable', 'boolean'], 'status' => ['nullable', 'string']])]);
            return $this->success(['leave_type' => DB::table('leave_types')->where('id', $id)->first()], 'Leave type created.', 201);
        }

        return $this->success(['leave_types' => $this->base('leave_types')->orderBy('name')->get()]);
    }

    public function payrollDashboard(): JsonResponse
    {
        return $this->success(['dashboard' => [
            'cards' => [
                'cycles' => $this->base('payroll_cycles')->count(),
                'gross_payroll' => (float) $this->base('payrolls')->sum('gross_salary'),
                'net_payroll' => (float) $this->base('payrolls')->sum('net_salary'),
                'pending_reimbursements' => $this->base('payroll_reimbursements')->where('approval_status', 'pending')->count(),
                'pending_bank_transfers' => $this->base('payroll_bank_transfers')->where('status', 'pending')->count(),
            ],
            'current_cycles' => $this->base('payroll_cycles')->orderByDesc('payroll_year')->orderByDesc('payroll_month')->limit(8)->get(),
            'recent_payrolls' => $this->payrollRows()->orderByDesc('payrolls.id')->limit(10)->get(),
            'component_breakdown' => $this->base('payroll_items')->join('payroll_components', 'payroll_components.id', '=', 'payroll_items.component_id')->groupBy('payroll_components.name')->selectRaw('payroll_components.name, sum(payroll_items.amount) as amount')->get(),
        ]]);
    }

    public function payrollCycles(Request $request): JsonResponse
    {
        $page = $this->filterSearch($this->base('payroll_cycles'), $request, ['cycle_name', 'status'])->orderByDesc('payroll_year')->orderByDesc('payroll_month')->paginate($request->integer('per_page', 25));
        return $this->list($page->items(), $page);
    }

    public function storePayrollCycle(Request $request): JsonResponse
    {
        $id = DB::table('payroll_cycles')->insertGetId([...$this->payrollCyclePayload($request), 'uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'created_at' => now(), 'updated_at' => now()]);
        return $this->success(['cycle' => $this->payrollCycleBundle($id)], 'Payroll cycle created.', 201);
    }

    public function showPayrollCycle(string $cycle_uuid): JsonResponse { return $this->success(['cycle' => $this->payrollCycleBundle($this->find('payroll_cycles', $cycle_uuid)->id)]); }
    public function updatePayrollCycle(Request $request, string $cycle_uuid): JsonResponse { $cycle = $this->find('payroll_cycles', $cycle_uuid); DB::table('payroll_cycles')->where('id', $cycle->id)->update([...$this->payrollCyclePayload($request, true), 'updated_at' => now()]); return $this->success(['cycle' => $this->payrollCycleBundle($cycle->id)], 'Payroll cycle updated.'); }

    public function payrollPreview(Request $request, string $cycle_uuid): JsonResponse
    {
        $cycle = $this->find('payroll_cycles', $cycle_uuid);
        return $this->success(['preview' => $this->buildPayrollPreview($cycle, $request), 'validations' => $this->payrollValidations($cycle)]);
    }

    public function generatePayroll(Request $request, string $cycle_uuid): JsonResponse
    {
        $cycle = $this->find('payroll_cycles', $cycle_uuid);
        $preview = $this->buildPayrollPreview($cycle, $request);
        foreach ($preview as $row) {
            DB::table('payrolls')->updateOrInsert(
                ['tenant_id' => $this->tenantId(), 'payroll_cycle_id' => $cycle->id, 'staff_id' => $row['staff_id']],
                [
                    'uuid' => (string) Str::uuid(),
                    'employee_code' => $row['employee_code'],
                    'working_days' => $row['working_days'],
                    'present_days' => $row['present_days'],
                    'leave_days' => $row['leave_days'],
                    'gross_salary' => $row['gross_salary'],
                    'total_earnings' => $row['total_earnings'],
                    'total_deductions' => $row['total_deductions'],
                    'net_salary' => $row['net_salary'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
        DB::table('payroll_cycles')->where('id', $cycle->id)->update(['status' => 'generated', 'processed_by' => $request->user()?->id, 'processed_at' => now(), 'updated_at' => now()]);

        return $this->success(['cycle' => $this->payrollCycleBundle($cycle->id)], 'Payroll generated.');
    }

    public function payrollCycleAction(Request $request, string $cycle_uuid, string $action): JsonResponse
    {
        $cycle = $this->find('payroll_cycles', $cycle_uuid);
        $status = ['submit' => 'submitted', 'approve' => 'approved', 'lock' => 'locked', 'reopen' => 'draft'][$action] ?? abort(404);
        $payload = ['status' => $status, 'remarks' => $request->input('remarks', $cycle->remarks), 'updated_at' => now()];
        if ($action === 'approve') $payload += ['approved_by' => $request->user()?->id, 'approved_at' => now()];
        DB::table('payroll_cycles')->where('id', $cycle->id)->update($payload);

        return $this->success(['cycle' => $this->payrollCycleBundle($cycle->id)], 'Payroll cycle '.$status.'.');
    }

    public function payrolls(Request $request): JsonResponse { $page = $this->filterSearch($this->payrollRows(), $request, ['staff.display_name', 'payrolls.employee_code'])->orderByDesc('payrolls.id')->paginate($request->integer('per_page', 25)); return $this->list($page->items(), $page); }
    public function showPayroll(string $payroll_uuid): JsonResponse { return $this->success(['payroll' => $this->payrollBundle($this->find('payrolls', $payroll_uuid)->id)]); }
    public function updatePayroll(Request $request, string $payroll_uuid): JsonResponse { $payroll = $this->find('payrolls', $payroll_uuid); DB::table('payrolls')->where('id', $payroll->id)->update($request->only(['working_days','present_days','leave_days','unpaid_leave_days','overtime_hours','gross_salary','total_earnings','total_deductions','taxable_income','tax_amount','net_salary','payment_status','payment_reference','remarks'])); return $this->success(['payroll' => $this->payrollBundle($payroll->id)], 'Payroll updated.'); }
    public function payrollItems(string $payroll_uuid): JsonResponse { $payroll = $this->find('payrolls', $payroll_uuid); return $this->success(['items' => $this->base('payroll_items')->where('payroll_id', $payroll->id)->get()]); }
    public function payslips(Request $request): JsonResponse { return $this->simplePaged($request, $this->base('payroll_payslips')->leftJoin('payrolls', 'payrolls.id', '=', 'payroll_payslips.payroll_id')->leftJoin('staff', 'staff.id', '=', 'payrolls.staff_id')->select('payroll_payslips.*', 'staff.display_name as staff_name', 'payrolls.employee_code')); }
    public function generatePayslips(Request $request): JsonResponse { return $this->queued($request, 'generate', 'payslips'); }
    public function emailPayslips(Request $request): JsonResponse { return $this->queued($request, 'email', 'payslips'); }
    public function downloadPayslip(int $payslip_id): JsonResponse { $row = $this->scoped('payroll_payslips', $payslip_id); return $this->success(['payslip' => $row, 'download' => ['status' => 'metadata_only', 'message' => 'File download is available when a payslip file is generated.']]); }
    public function componentTypes(Request $request): JsonResponse { return $this->simpleIndexStore($request, 'payroll_component_types', ['name','code','calculation_side','status'], 'component_types'); }
    public function components(Request $request): JsonResponse { return $this->simpleIndexStore($request, 'payroll_components', ['component_type_id','name','code','calculation_method','default_value','formula','taxable','affects_pf','affects_esi','status'], 'components', ['component_type_id' => 'payroll_component_types']); }
    public function updateComponent(Request $request, int $component_id): JsonResponse { return $this->simpleUpdate($request, 'payroll_components', $component_id, ['component_type_id','name','code','calculation_method','default_value','formula','taxable','affects_pf','affects_esi','status'], ['component_type_id' => 'payroll_component_types'], 'component'); }
    public function componentAssignments(Request $request): JsonResponse { return $this->simpleIndexStore($request, 'payroll_component_assignments', ['staff_id','component_id','amount','effective_from','effective_to'], 'assignments', ['staff_id' => 'staff', 'component_id' => 'payroll_components']); }
    public function loans(Request $request): JsonResponse { return $this->simpleIndexStore($request, 'payroll_loans', ['staff_id','loan_number','principal_amount','interest_rate','installment_amount','remaining_amount','total_installments','issued_date','status'], 'loans', ['staff_id' => 'staff']); }
    public function updateLoan(Request $request, int $loan_id): JsonResponse { return $this->simpleUpdate($request, 'payroll_loans', $loan_id, ['staff_id','loan_number','principal_amount','interest_rate','installment_amount','remaining_amount','total_installments','issued_date','status'], ['staff_id' => 'staff'], 'loan'); }
    public function reimbursements(Request $request): JsonResponse { return $this->simpleIndexStore($request, 'payroll_reimbursements', ['payroll_id','staff_id','expense_id','amount','approval_status'], 'reimbursements', ['payroll_id' => 'payrolls', 'staff_id' => 'staff']); }
    public function approveReimbursement(Request $request, int $reimbursement_id): JsonResponse { $row = $this->scoped('payroll_reimbursements', $reimbursement_id); DB::table('payroll_reimbursements')->where('id', $row->id)->update(['approval_status' => $request->input('status', 'approved')]); return $this->success(['reimbursement' => DB::table('payroll_reimbursements')->where('id', $row->id)->first()], 'Reimbursement updated.'); }
    public function bankTransfers(Request $request): JsonResponse { return $this->simpleIndexStore($request, 'payroll_bank_transfers', ['payroll_id','bank_account_id','reference','amount','transfer_date','status'], 'bank_transfers', ['payroll_id' => 'payrolls']); }
    public function markTransferPaid(Request $request, int $transfer_id): JsonResponse { $row = $this->scoped('payroll_bank_transfers', $transfer_id); DB::table('payroll_bank_transfers')->where('id', $row->id)->update(['status' => 'paid', 'transfer_date' => $request->input('transfer_date', today()->toDateString()), 'reference' => $request->input('reference', $row->reference)]); return $this->success(['transfer' => DB::table('payroll_bank_transfers')->where('id', $row->id)->first()], 'Bank transfer marked paid.'); }
    public function taxSlabs(Request $request): JsonResponse { return $this->simpleIndexStore($request, 'payroll_tax_slabs', ['name','min_amount','max_amount','tax_percentage','cess_percentage','effective_from','effective_to'], 'tax_slabs'); }
    public function pfSettings(Request $request): JsonResponse { return $this->settingsEndpoint($request, 'payroll_pf_settings', 'pf_settings'); }
    public function esiSettings(Request $request): JsonResponse { return $this->settingsEndpoint($request, 'payroll_esi_settings', 'esi_settings'); }
    public function exportPayroll(Request $request): JsonResponse { return $this->queued($request, 'export', 'payroll'); }

    public function holidays(Request $request): JsonResponse { return $this->simplePaged($request, $this->holidayRows()); }
    public function storeHoliday(Request $request): JsonResponse { $id = DB::table('holidays')->insertGetId([...$this->holidayPayload($request), 'uuid' => (string) Str::uuid(), 'tenant_id' => $this->tenantId(), 'created_by' => $request->user()?->id, 'updated_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]); $this->syncHolidayApplicabilities($id, $request->input('applicabilities', [])); return $this->success(['holiday' => $this->holidayBundle($id)], 'Holiday created.', 201); }
    public function showHoliday(string $holiday_uuid): JsonResponse { return $this->success(['holiday' => $this->holidayBundle($this->find('holidays', $holiday_uuid)->id)]); }
    public function updateHoliday(Request $request, string $holiday_uuid): JsonResponse { $holiday = $this->find('holidays', $holiday_uuid); DB::table('holidays')->where('id', $holiday->id)->update([...$this->holidayPayload($request, true), 'updated_by' => $request->user()?->id, 'updated_at' => now()]); if ($request->has('applicabilities')) $this->syncHolidayApplicabilities($holiday->id, $request->input('applicabilities', [])); return $this->success(['holiday' => $this->holidayBundle($holiday->id)], 'Holiday updated.'); }
    public function deleteHoliday(string $holiday_uuid): JsonResponse { $holiday = $this->find('holidays', $holiday_uuid); DB::table('holidays')->where('id', $holiday->id)->update(['deleted_at' => now(), 'updated_at' => now()]); return $this->success(null, 'Holiday deleted.'); }
    public function duplicateHoliday(string $holiday_uuid): JsonResponse { $holiday = (array) $this->find('holidays', $holiday_uuid); foreach (['id','uuid','created_at','updated_at','deleted_at'] as $key) unset($holiday[$key]); foreach (['holiday_date','start_date','end_date'] as $key) if (!empty($holiday[$key])) $holiday[$key] = \Carbon\Carbon::parse($holiday[$key])->addYear()->toDateString(); $holiday['uuid'] = (string) Str::uuid(); $holiday['name'] .= ' '.\Carbon\Carbon::parse($holiday['holiday_date'])->year; $holiday['created_at'] = now(); $holiday['updated_at'] = now(); $id = DB::table('holidays')->insertGetId($holiday); return $this->success(['holiday' => $this->holidayBundle($id)], 'Holiday duplicated.', 201); }
    public function importHolidays(Request $request): JsonResponse { return $this->queued($request, 'import', 'holidays'); }
    public function exportHolidays(Request $request): JsonResponse { return $this->queued($request, 'export', 'holidays'); }
    public function holidayCalendars(Request $request): JsonResponse { return $this->simpleIndexStore($request, 'holiday_calendars', ['name','description','country_id','state_id','is_default','status'], 'calendars'); }
    public function showHolidayCalendar(string $calendar_uuid): JsonResponse { $calendar = $this->find('holiday_calendars', $calendar_uuid); return $this->success(['calendar' => $calendar, 'holidays' => $this->holidayRows()->where('holidays.holiday_calendar_id', $calendar->id)->get()]); }
    public function updateHolidayCalendar(Request $request, string $calendar_uuid): JsonResponse { $calendar = $this->find('holiday_calendars', $calendar_uuid); DB::table('holiday_calendars')->where('id', $calendar->id)->update($request->only(['name','description','country_id','state_id','is_default','status'])); return $this->success(['calendar' => DB::table('holiday_calendars')->where('id', $calendar->id)->first()], 'Holiday calendar updated.'); }
    public function deleteHolidayCalendar(string $calendar_uuid): JsonResponse { $calendar = $this->find('holiday_calendars', $calendar_uuid); DB::table('holiday_calendars')->where('id', $calendar->id)->delete(); return $this->success(null, 'Holiday calendar deleted.'); }
    public function holidayGroups(Request $request): JsonResponse { return $this->simpleIndexStore($request, 'holiday_groups', ['name','description','status'], 'groups'); }
    public function updateHolidayGroup(Request $request, string $group_uuid): JsonResponse { $group = $this->find('holiday_groups', $group_uuid); DB::table('holiday_groups')->where('id', $group->id)->update($request->only(['name','description','status'])); return $this->success(['group' => DB::table('holiday_groups')->where('id', $group->id)->first()], 'Holiday group updated.'); }
    public function holidayGroupMembers(Request $request, string $group_uuid): JsonResponse { $group = $this->find('holiday_groups', $group_uuid); if ($request->isMethod('post')) { foreach ((array) $request->input('staff_ids', [$request->input('staff_id')]) as $staff) DB::table('holiday_group_members')->updateOrInsert(['tenant_id' => $this->tenantId(), 'holiday_group_id' => $group->id, 'staff_id' => $this->staffInputToId($staff)], ['assigned_at' => now()]); } return $this->success(['members' => $this->holidayGroupMemberRows()->where('holiday_group_members.holiday_group_id', $group->id)->get()]); }
    public function deleteHolidayGroupMember(string $group_uuid, string $staff_uuid): JsonResponse { $group = $this->find('holiday_groups', $group_uuid); DB::table('holiday_group_members')->where('tenant_id', $this->tenantId())->where('holiday_group_id', $group->id)->where('staff_id', $this->staffInputToId($staff_uuid))->delete(); return $this->success(null, 'Holiday group member removed.'); }

    private function tenantId(): int { return app(TenantContext::class)->id(); }
    private function base(string $table) { $query = DB::table($table)->where($table.'.tenant_id', $this->tenantId()); if (Schema::hasColumn($table, 'deleted_at')) $query->whereNull($table.'.deleted_at'); return $query; }
    private function find(string $table, string $uuid): object { return $this->base($table)->where($table.'.uuid', $uuid)->first() ?: abort(404, 'Resource not found.'); }
    private function scoped(string $table, int $id): object { return $this->base($table)->where($table.'.id', $id)->first() ?: abort(404, 'Resource not found.'); }
    private function count(string $table): int { return Schema::hasTable($table) ? $this->base($table)->count() : 0; }

    private function idFrom(string $table, mixed $value): ?int
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (int) $value;
        return $this->base($table)->where('uuid', $value)->value('id') ?: abort(404, 'Referenced resource not found.');
    }

    private function staffInputToId(mixed $value): ?int { return $this->idFrom('staff', $value); }
    private function staffIdForUser(Request $request): int { $email = $request->user()?->email; return (int) ($this->base('staff')->where(fn ($q) => $q->where('work_email', $email)->orWhere('personal_email', $email))->value('id') ?: $this->base('staff')->value('id') ?: abort(422, 'No staff profile is available for attendance.')); }
    private function queued(Request $request, string $type, string $module): JsonResponse { return $this->success(['job' => $this->tenant->createJob($request, $type, $module, $request->all())], ucfirst($module).' '.$type.' queued.', 202); }
    private function filterSearch($query, Request $request, array $columns) { if ($search = $request->input('search')) $query->where(fn ($inner) => collect($columns)->each(fn ($column) => $inner->orWhere($column, 'like', '%'.$search.'%'))); return $query; }
    private function simplePaged(Request $request, $query): JsonResponse { $page = $query->paginate($request->integer('per_page', 25)); return $this->list($page->items(), $page); }

    private function attendanceRows() { return $this->base('attendance_records')->join('staff', 'staff.id', '=', 'attendance_records.staff_id')->leftJoin('tenant_lookups as status', 'status.id', '=', 'attendance_records.status_id')->select('attendance_records.*', 'staff.uuid as staff_uuid', 'staff.display_name as staff_name', 'staff.employee_code', 'status.name as status_name'); }
    private function attendanceRequestRows() { return $this->base('attendance_requests')->join('staff', 'staff.id', '=', 'attendance_requests.staff_id')->select('attendance_requests.*', 'staff.uuid as staff_uuid', 'staff.display_name as staff_name', 'staff.employee_code'); }
    private function leaveRequestRows() { return $this->base('leave_requests')->join('staff', 'staff.id', '=', 'leave_requests.staff_id')->join('leave_types', 'leave_types.id', '=', 'leave_requests.leave_type_id')->leftJoin('tenant_lookups as status', 'status.id', '=', 'leave_requests.status_id')->select('leave_requests.*', 'staff.uuid as staff_uuid', 'staff.display_name as staff_name', 'staff.employee_code', 'leave_types.name as leave_type_name', 'leave_types.uuid as leave_type_uuid', 'status.name as status_name'); }
    private function leaveBalanceRows() { return $this->base('leave_balances')->join('staff', 'staff.id', '=', 'leave_balances.staff_id')->join('leave_types', 'leave_types.id', '=', 'leave_balances.leave_type_id')->select('leave_balances.*', 'staff.uuid as staff_uuid', 'staff.display_name as staff_name', 'staff.employee_code', 'leave_types.name as leave_type_name', 'leave_types.uuid as leave_type_uuid'); }
    private function payrollRows() { return $this->base('payrolls')->join('staff', 'staff.id', '=', 'payrolls.staff_id')->join('payroll_cycles', 'payroll_cycles.id', '=', 'payrolls.payroll_cycle_id')->select('payrolls.*', 'staff.uuid as staff_uuid', 'staff.display_name as staff_name', 'payroll_cycles.uuid as cycle_uuid', 'payroll_cycles.cycle_name'); }
    private function holidayRows() { return $this->base('holidays')->join('holiday_calendars', 'holiday_calendars.id', '=', 'holidays.holiday_calendar_id')->select('holidays.*', 'holiday_calendars.uuid as calendar_uuid', 'holiday_calendars.name as calendar_name'); }
    private function holidayGroupMemberRows() { return $this->base('holiday_group_members')->join('staff', 'staff.id', '=', 'holiday_group_members.staff_id')->select('holiday_group_members.*', 'staff.uuid as staff_uuid', 'staff.display_name as staff_name', 'staff.employee_code'); }

    private function attendancePayload(Request $request, bool $partial = false): array { $data = $request->only(['staff_id','attendance_date','check_in_at','check_out_at','total_minutes','status_id']); if (!$partial) $data = array_filter($data, fn ($v) => $v !== null && $v !== ''); if (array_key_exists('staff_id', $data)) $data['staff_id'] = $this->staffInputToId($data['staff_id']); if (array_key_exists('status_id', $data)) $data['status_id'] = $this->idFrom('tenant_lookups', $data['status_id']); return $data; }
    private function leaveRequestPayload(Request $request): array { $data = $request->validate(['staff_id' => ['nullable'], 'leave_type_id' => ['required'], 'start_date' => ['required', 'date'], 'end_date' => ['required', 'date'], 'total_days' => ['required', 'numeric'], 'reason' => ['nullable', 'string'], 'status_id' => ['nullable']]); $data['staff_id'] = $this->staffInputToId($data['staff_id'] ?? null) ?? $this->staffIdForUser($request); $data['leave_type_id'] = $this->idFrom('leave_types', $data['leave_type_id']); if (isset($data['status_id'])) $data['status_id'] = $this->idFrom('tenant_lookups', $data['status_id']); return $data; }
    private function payrollCyclePayload(Request $request, bool $partial = false): array { $fields = ['cycle_name','payroll_month','payroll_year','period_start','period_end','payment_date','status','remarks']; $data = $request->only($fields); return $partial ? $data : array_filter($data, fn ($v) => $v !== null && $v !== ''); }
    private function holidayPayload(Request $request, bool $partial = false): array { $fields = ['holiday_calendar_id','name','type_id','category_id','holiday_date','start_date','end_date','total_days','is_half_day','half_day_session','recurring_yearly','optional_holiday','applicable_to_all','description','color']; $data = $request->only($fields); if (!$partial) $data = array_filter($data, fn ($v) => $v !== null && $v !== ''); foreach (['holiday_calendar_id' => 'holiday_calendars', 'type_id' => 'tenant_lookups', 'category_id' => 'tenant_lookups'] as $key => $table) if (array_key_exists($key, $data)) $data[$key] = $this->idFrom($table, $data[$key]); return $data; }

    private function attendanceRequestAction(Request $request, string $uuid, string $status): JsonResponse { $row = $this->attendanceRequestRows()->where('attendance_requests.uuid', $uuid)->first() ?: abort(404); DB::table('attendance_requests')->where('id', $row->id)->update(['status' => $status, 'approved_by' => $request->user()?->id, 'approved_at' => now(), 'updated_at' => now()]); return $this->success(['request' => $this->attendanceRequestRows()->where('attendance_requests.id', $row->id)->first()], 'Attendance request '.$status.'.'); }
    private function leaveRequestAction(Request $request, int $id, string $status): JsonResponse { $row = $this->scoped('leave_requests', $id); DB::table('leave_requests')->where('id', $row->id)->update(['approved_by' => $request->user()?->id, 'approved_at' => now(), 'updated_at' => now()]); return $this->success(['request' => $this->leaveRequestRows()->where('leave_requests.id', $row->id)->first(), 'status' => $status], 'Leave request '.$status.'.'); }
    private function payrollCycleBundle(int $id): array { $cycle = DB::table('payroll_cycles')->where('id', $id)->first(); return ['cycle' => $cycle, 'payrolls' => $this->payrollRows()->where('payrolls.payroll_cycle_id', $id)->get()]; }
    private function payrollBundle(int $id): array { return ['payroll' => $this->payrollRows()->where('payrolls.id', $id)->first(), 'items' => $this->base('payroll_items')->where('payroll_id', $id)->get(), 'overtime' => $this->base('payroll_overtime')->where('payroll_id', $id)->get(), 'approvals' => $this->base('payroll_approvals')->where('payroll_id', $id)->get(), 'payslip' => $this->base('payroll_payslips')->where('payroll_id', $id)->first()]; }
    private function holidayBundle(int $id): array { return ['holiday' => $this->holidayRows()->where('holidays.id', $id)->first(), 'applicabilities' => $this->base('holiday_applicabilities')->where('holiday_id', $id)->get()]; }

    private function buildPayrollPreview(object $cycle, Request $request): array { $staffIds = (array) $request->input('staff_ids', []); $staff = $this->base('staff')->when($staffIds, fn ($q) => $q->whereIn('uuid', $staffIds))->limit(100)->get(); return $staff->map(fn ($row) => ['staff_id' => $row->id, 'staff_uuid' => $row->uuid, 'staff_name' => $row->display_name, 'employee_code' => $row->employee_code, 'working_days' => 26, 'present_days' => $this->base('attendance_records')->where('staff_id', $row->id)->whereBetween('attendance_date', [$cycle->period_start, $cycle->period_end])->whereNotNull('check_in_at')->count(), 'leave_days' => (float) $this->base('leave_requests')->where('staff_id', $row->id)->whereBetween('start_date', [$cycle->period_start, $cycle->period_end])->sum('total_days'), 'gross_salary' => (float) ($this->base('staff_salary_structures')->where('staff_id', $row->id)->orderByDesc('effective_from')->value('monthly_gross') ?: 0), 'total_earnings' => (float) ($this->base('staff_salary_structures')->where('staff_id', $row->id)->orderByDesc('effective_from')->value('monthly_gross') ?: 0), 'total_deductions' => 0, 'net_salary' => (float) ($this->base('staff_salary_structures')->where('staff_id', $row->id)->orderByDesc('effective_from')->value('monthly_gross') ?: 0)])->all(); }
    private function payrollValidations(object $cycle): array { return ['missing_salary_structures' => $this->base('staff')->leftJoin('staff_salary_structures', 'staff_salary_structures.staff_id', '=', 'staff.id')->whereNull('staff_salary_structures.id')->count(), 'cycle_period' => [$cycle->period_start, $cycle->period_end]]; }
    private function simpleIndexStore(Request $request, string $table, array $fields, string $key, array $idMaps = []): JsonResponse { if ($request->isMethod('post')) { $payload = $request->only($fields); foreach ($idMaps as $field => $mapTable) if (array_key_exists($field, $payload)) $payload[$field] = $this->idFrom($mapTable, $payload[$field]); $uuid = Schema::hasColumn($table, 'uuid') ? ['uuid' => (string) Str::uuid()] : []; $id = DB::table($table)->insertGetId(['tenant_id' => $this->tenantId(), ...$uuid, ...$payload, ...$this->timestampsFor($table)]); return $this->success([$this->singularKey($key) => DB::table($table)->where('id', $id)->first()], ucfirst(str_replace('_', ' ', $this->singularKey($key))).' created.', 201); } return $this->success([$key => $this->base($table)->orderByDesc('id')->get()]); }
    private function simpleUpdate(Request $request, string $table, int $id, array $fields, array $idMaps, string $key): JsonResponse { $this->scoped($table, $id); $payload = $request->only($fields); foreach ($idMaps as $field => $mapTable) if (array_key_exists($field, $payload)) $payload[$field] = $this->idFrom($mapTable, $payload[$field]); DB::table($table)->where('id', $id)->update($payload); return $this->success([$key => DB::table($table)->where('id', $id)->first()], ucfirst($key).' updated.'); }
    private function settingsEndpoint(Request $request, string $table, string $key): JsonResponse { if ($request->isMethod('put')) { $payload = $request->only(['employee_rate','employer_rate','wage_limit','effective_from']); $id = $this->base($table)->orderByDesc('id')->value('id'); $id ? DB::table($table)->where('id', $id)->update($payload) : DB::table($table)->insert(['tenant_id' => $this->tenantId(), ...$payload]); } return $this->success([$key => $this->base($table)->orderByDesc('id')->first()]); }
    private function syncHolidayApplicabilities(int $holidayId, array $items): void { DB::table('holiday_applicabilities')->where('tenant_id', $this->tenantId())->where('holiday_id', $holidayId)->delete(); foreach ($items as $item) DB::table('holiday_applicabilities')->insert(['tenant_id' => $this->tenantId(), 'holiday_id' => $holidayId, 'applicable_type' => $item['applicable_type'] ?? 'staff', 'applicable_id' => (int) ($item['applicable_id'] ?? 0), 'created_at' => now()]); }
    private function timestampsFor(string $table): array { return Schema::hasColumn($table, 'created_at') ? ['created_at' => now(), 'updated_at' => now()] : []; }
    private function singularKey(string $key): string { return Str::singular($key); }
}
