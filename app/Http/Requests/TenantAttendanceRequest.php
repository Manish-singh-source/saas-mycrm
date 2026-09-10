<?php
namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class TenantAttendanceRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'daily' => ['date' => ['nullable', 'date_format:Y-m-d'], 'search' => ['nullable', 'string', 'max:200'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'between:1,100']],
            'monthly' => ['month' => ['nullable', 'integer', 'between:1,12'], 'year' => ['nullable', 'integer', 'between:2000,2100']],
            'storeRecord', 'updateRecord' => ['staff_id' => ['sometimes', 'required'], 'attendance_date' => ['sometimes', 'date_format:Y-m-d'], 'check_in_at' => ['nullable', 'date'], 'check_out_at' => ['nullable', 'date', 'after_or_equal:check_in_at'], 'total_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'], 'status_id' => ['nullable']],
            'storeRequest' => ['staff_id' => ['nullable'], 'attendance_record_id' => ['nullable', 'integer'], 'request_date' => ['nullable', 'date_format:Y-m-d'], 'request_type' => ['required', 'string', 'max:80'], 'reason' => ['nullable', 'string', 'max:2000']],            'leaveRequests' => ['search' => ['nullable', 'string', 'max:200'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'between:1,100']],
            'storeLeaveRequest' => ['staff_id' => ['nullable'], 'leave_type_id' => ['required'], 'start_date' => ['required', 'date_format:Y-m-d'], 'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'], 'total_days' => ['required', 'numeric', 'min:0.5'], 'reason' => ['nullable', 'string', 'max:2000'], 'status_id' => ['nullable']],
            'adjustLeaveBalance' => ['staff_id' => ['required'], 'leave_type_id' => ['required'], 'year' => ['nullable', 'integer', 'between:2000,2100'], 'remaining' => ['required', 'numeric', 'min:0'], 'remarks' => ['nullable', 'string', 'max:2000']],
            'leaveCalendar' => ['date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'], 'staff_id' => ['nullable'], 'leave_type_id' => ['nullable'], 'status' => ['nullable', 'string', 'max:50']],
            'storeLeaveType' => ['name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:80'], 'paid' => ['nullable', 'boolean'], 'carry_forward' => ['nullable', 'boolean'], 'status' => ['nullable', 'string', 'max:50']],
            'requests' => ['search' => ['nullable', 'string', 'max:200'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'between:1,100']],
            'exportAttendance' => ['format' => ['nullable', Rule::in(['csv', 'xlsx', 'json'])], 'date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from']],
            default => [],
        };
    }
}