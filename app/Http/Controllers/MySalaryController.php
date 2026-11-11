<?php

namespace App\Http\Controllers;

use App\Models\MonthlySalary;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MySalaryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewOwn', MonthlySalary::class);

        $employee = $request->user()->employee;
        abort_unless($employee !== null, 403);

        $records = MonthlySalary::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('salary_month')
            ->orderByDesc('id')
            ->paginate(25);

        return view('salaries.mine', [
            'records' => $records,
            'currency' => config('hrms.currency'),
        ]);
    }
}
