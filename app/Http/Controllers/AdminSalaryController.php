<?php

namespace App\Http\Controllers;

use App\Enums\SalaryStatus;
use App\Http\Requests\StoreMonthlySalaryRequest;
use App\Http\Requests\UpdateMonthlySalaryRequest;
use App\Models\AuditEvent;
use App\Models\Employee;
use App\Models\MonthlySalary;
use App\Salary\SalaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminSalaryController extends Controller
{
    public function index(Request $request, SalaryService $salaries): View
    {
        $this->authorize('manage', MonthlySalary::class);

        $employeeId = $request->integer('employee_id') ?: null;
        $month = $request->string('salary_month')->toString();
        $status = $request->string('given_status')->toString();

        $normalizedMonth = null;
        if ($month !== '') {
            try {
                $normalizedMonth = $salaries->normalizeMonth($month);
            } catch (ValidationException) {
                $normalizedMonth = null;
            }
        }

        $baseQuery = MonthlySalary::query()
            ->with(['employee.user'])
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($normalizedMonth, fn ($q) => $q->whereDate('salary_month', $normalizedMonth))
            ->when(in_array($status, ['paid', 'unpaid'], true), fn ($q) => $q->where('given_status', $status));

        $records = (clone $baseQuery)
            ->orderByDesc('salary_month')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $paidCount = 0;
        $unpaidCount = 0;
        $paidTotal = '0.00';
        $unpaidTotal = '0.00';

        MonthlySalary::query()
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($normalizedMonth, fn ($q) => $q->whereDate('salary_month', $normalizedMonth))
            ->when(in_array($status, ['paid', 'unpaid'], true), fn ($q) => $q->where('given_status', $status))
            ->orderBy('id')
            ->each(function (MonthlySalary $row) use (&$paidCount, &$unpaidCount, &$paidTotal, &$unpaidTotal): void {
                $total = $row->totalAmount();
                if ($row->given_status === SalaryStatus::Paid) {
                    $paidCount++;
                    $paidTotal = bcadd($paidTotal, $total, 2);
                } else {
                    $unpaidCount++;
                    $unpaidTotal = bcadd($unpaidTotal, $total, 2);
                }
            });

        return view('salaries.index', [
            'records' => $records,
            'employees' => Employee::query()->with('user')->orderBy('id')->get(),
            'filters' => [
                'employee_id' => $employeeId,
                'salary_month' => $month,
                'given_status' => $status,
            ],
            'paidCount' => $paidCount,
            'unpaidCount' => $unpaidCount,
            'paidTotal' => $paidTotal,
            'unpaidTotal' => $unpaidTotal,
            'currency' => config('hrms.currency'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', MonthlySalary::class);

        return view('salaries.create', [
            'employees' => Employee::query()->with('user')->orderBy('id')->get(),
            'currency' => config('hrms.currency'),
            'defaultMonth' => now(config('hrms.display_timezone'))->format('Y-m'),
        ]);
    }

    public function store(StoreMonthlySalaryRequest $request, SalaryService $salaries): RedirectResponse
    {
        $employee = Employee::query()->findOrFail($request->validated('employee_id'));
        $record = $salaries->create($employee, $request->user(), $request->validated());

        return redirect()
            ->route('salaries.show', $record)
            ->with('status', __('Monthly salary saved as unpaid tracking record.'));
    }

    public function show(MonthlySalary $salary): View
    {
        $this->authorize('view', $salary);
        $salary->loadMissing(['employee.user', 'payer']);

        $audits = AuditEvent::query()
            ->where('entity_type', MonthlySalary::class)
            ->where('entity_id', $salary->id)
            ->with('actor')
            ->orderByDesc('id')
            ->get();

        return view('salaries.show', [
            'record' => $salary,
            'audits' => $audits,
            'currency' => config('hrms.currency'),
        ]);
    }

    public function edit(MonthlySalary $salary): View
    {
        $this->authorize('update', $salary);
        $salary->loadMissing('employee.user');

        return view('salaries.edit', [
            'record' => $salary,
            'currency' => config('hrms.currency'),
        ]);
    }

    public function update(
        UpdateMonthlySalaryRequest $request,
        MonthlySalary $salary,
        SalaryService $salaries
    ): RedirectResponse {
        $salaries->updateAmounts($salary, $request->user(), $request->validated());

        return redirect()
            ->route('salaries.show', $salary)
            ->with('status', __('Salary amounts updated.'));
    }
}
