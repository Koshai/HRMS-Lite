<?php

namespace App\Http\Controllers;

use App\Attendance\AttendanceService;
use App\Models\AttendanceRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyAttendanceController extends Controller
{
    public function index(Request $request, AttendanceService $attendance): View
    {
        $this->authorize('viewOwn', AttendanceRecord::class);

        $employee = $request->user()->employee;
        abort_unless($employee !== null, 404);

        $current = $attendance->getCurrentAttendance($employee);
        $open = $attendance->getOpenAttendance($employee);
        $history = $attendance->getAttendanceHistory($employee);

        return view('attendance.mine', [
            'employee' => $employee,
            'current' => $current,
            'open' => $open,
            'history' => $history,
            'timezone' => $attendance->timezone(),
            'canCheckIn' => $open === null,
            'canCheckOut' => $open !== null,
            'formatLocal' => fn ($instant) => $attendance->formatLocal($instant),
        ]);
    }

    public function checkIn(Request $request, AttendanceService $attendance): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee !== null, 404);
        $this->authorize('punch', $employee);

        $attendance->checkIn($employee, $request->user());

        return redirect()
            ->route('attendance.mine')
            ->with('status', __('Checked in successfully.'));
    }

    public function checkOut(Request $request, AttendanceService $attendance): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee !== null, 404);
        $this->authorize('punch', $employee);

        $attendance->checkOut($employee, $request->user());

        return redirect()
            ->route('attendance.mine')
            ->with('status', __('Checked out successfully.'));
    }
}
