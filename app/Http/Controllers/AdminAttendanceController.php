<?php

namespace App\Http\Controllers;

use App\Attendance\AttendanceService;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAttendanceController extends Controller
{
    public function index(Request $request, AttendanceService $attendance): View
    {
        $this->authorize('manage', AttendanceRecord::class);

        $employeeId = $request->integer('employee_id') ?: null;
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();

        $records = AttendanceRecord::query()
            ->with(['employee.user'])
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->when($from !== '', fn ($query) => $query->whereDate('work_date', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('work_date', '<=', $to))
            ->orderByDesc('work_date')
            ->orderByDesc('check_in_at')
            ->paginate(25)
            ->withQueryString();

        return view('attendance.index', [
            'records' => $records,
            'employees' => Employee::query()->with('user')->orderBy('id')->get(),
            'filters' => [
                'employee_id' => $employeeId,
                'from' => $from,
                'to' => $to,
            ],
            'timezone' => $attendance->timezone(),
            'formatLocal' => fn ($instant) => $attendance->formatLocal($instant),
        ]);
    }
}
