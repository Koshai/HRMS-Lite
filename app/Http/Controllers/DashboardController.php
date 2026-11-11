<?php

namespace App\Http\Controllers;

use App\Attendance\AttendanceService;
use App\Enums\SalaryStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\MonthlySalary;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AttendanceService $attendance): View
    {
        $user = $request->user();
        $timezone = $attendance->timezone();
        $today = $attendance->localDateFor($attendance->nowUtc());
        $monthStart = now($timezone)->startOfMonth()->toDateString();

        $admin = null;
        if ($user->can('access-admin')) {
            $activeEmployees = Employee::query()->where('is_active', true)->count();
            $checkedInToday = AttendanceRecord::query()
                ->whereNull('check_out_at')
                ->whereHas('employee', fn ($q) => $q->where('is_active', true))
                ->count();

            $monthSalaries = MonthlySalary::query()
                ->whereDate('salary_month', $monthStart)
                ->get(['given_status', 'salary_amount', 'bonus_amount']);

            $paidCount = 0;
            $unpaidCount = 0;
            $paidTotal = '0.00';
            $unpaidTotal = '0.00';
            foreach ($monthSalaries as $row) {
                $total = $row->totalAmount();
                if ($row->given_status === SalaryStatus::Paid) {
                    $paidCount++;
                    $paidTotal = bcadd($paidTotal, $total, 2);
                } else {
                    $unpaidCount++;
                    $unpaidTotal = bcadd($unpaidTotal, $total, 2);
                }
            }

            $missingSalaryEmployees = max(0, $activeEmployees - $monthSalaries->count());

            $admin = [
                'activeEmployees' => $activeEmployees,
                'checkedInToday' => $checkedInToday,
                'paidCount' => $paidCount,
                'unpaidCount' => $unpaidCount,
                'paidTotal' => $paidTotal,
                'unpaidTotal' => $unpaidTotal,
                'missingSalaryEmployees' => $missingSalaryEmployees,
                'monthLabel' => now($timezone)->format('Y-m'),
            ];
        }

        $employee = null;
        if ($user->can('access-employee-self-service') && $user->employee) {
            $profile = $user->employee()->with('user')->first();
            $current = $attendance->getCurrentAttendance($profile);
            $latestSalary = MonthlySalary::query()
                ->where('employee_id', $profile->id)
                ->orderByDesc('salary_month')
                ->first();

            $open = $attendance->getOpenAttendance($profile);

            $employee = [
                'profile' => $profile,
                'current' => $current,
                'open' => $open,
                'canCheckIn' => $open === null,
                'canCheckOut' => $open !== null,
                'latestSalary' => $latestSalary,
                'formatLocal' => fn ($instant) => $attendance->formatLocal($instant),
            ];
        }

        return view('dashboard', [
            'admin' => $admin,
            'employeePanel' => $employee,
            'timezone' => $timezone,
            'currency' => config('hrms.currency'),
            'today' => $today,
        ]);
    }
}
