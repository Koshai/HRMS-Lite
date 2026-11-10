<?php

namespace App\Http\Controllers;

use App\Attendance\AttendanceService;
use App\Http\Requests\StoreManualAttendanceRequest;
use App\Http\Requests\UpdateManualAttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\AuditEvent;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
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

    public function create(AttendanceService $attendance): View
    {
        $this->authorize('create', AttendanceRecord::class);

        return view('attendance.create', [
            'employees' => Employee::query()->with('user')->where('is_active', true)->orderBy('id')->get(),
            'timezone' => $attendance->timezone(),
        ]);
    }

    public function store(StoreManualAttendanceRequest $request, AttendanceService $attendance): RedirectResponse
    {
        $employee = Employee::query()->findOrFail($request->validated('employee_id'));
        $record = $attendance->createManual($employee, $request->user(), $request->validated());

        return redirect()
            ->route('attendance.show', $record)
            ->with('status', __('Manual attendance saved.'));
    }

    public function show(AttendanceRecord $attendance, AttendanceService $service): View
    {
        $this->authorize('view', $attendance);
        $attendance->loadMissing(['employee.user', 'creator', 'updater']);

        $audits = AuditEvent::query()
            ->where('entity_type', AttendanceRecord::class)
            ->where('entity_id', $attendance->id)
            ->with('actor')
            ->orderByDesc('id')
            ->get();

        return view('attendance.show', [
            'record' => $attendance,
            'audits' => $audits,
            'timezone' => $service->timezone(),
            'formatLocal' => fn ($instant) => $service->formatLocal($instant),
        ]);
    }

    public function edit(AttendanceRecord $attendance, AttendanceService $service): View
    {
        $this->authorize('update', $attendance);
        $attendance->loadMissing('employee.user');

        $checkInLocal = $attendance->check_in_at->setTimezone($service->timezone());
        $checkOutLocal = $attendance->check_out_at?->setTimezone($service->timezone());
        $checkoutNextDay = $checkOutLocal !== null
            && $checkOutLocal->toDateString() !== $attendance->work_date->toDateString();

        return view('attendance.edit', [
            'record' => $attendance,
            'timezone' => $service->timezone(),
            'checkInTime' => $checkInLocal->format('H:i'),
            'checkOutTime' => $checkOutLocal?->format('H:i'),
            'checkoutNextDay' => $checkoutNextDay,
        ]);
    }

    public function update(
        UpdateManualAttendanceRequest $request,
        AttendanceRecord $attendance,
        AttendanceService $service
    ): RedirectResponse {
        $service->correctManual($attendance, $request->user(), $request->validated());

        return redirect()
            ->route('attendance.show', $attendance)
            ->with('status', __('Attendance corrected. Work date was preserved.'));
    }
}
