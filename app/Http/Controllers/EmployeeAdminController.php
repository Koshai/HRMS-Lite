<?php

namespace App\Http\Controllers;

use App\Employees\EmployeePhotoService;
use App\Employees\EmployeeService;
use App\Enums\BloodGroup;
use App\Http\Requests\ResetEmployeePasswordRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeAdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

        $search = trim((string) $request->query('q', ''));

        $employees = Employee::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('designation', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Employee::class);

        return view('employees.create', [
            'bloodGroups' => BloodGroup::cases(),
        ]);
    }

    public function store(StoreEmployeeRequest $request, EmployeeService $employees): RedirectResponse
    {
        $employee = $employees->create($request->validated());

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', __('Employee provisioned. Share the temporary password securely outside this application.'));
    }

    public function show(Employee $employee): View
    {
        $this->authorize('view', $employee);
        $employee->loadMissing('user');

        return view('employees.show', [
            'employee' => $employee,
            'canViewSensitive' => request()->user()?->can('viewSensitive', $employee) ?? false,
        ]);
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);
        $employee->loadMissing('user');

        return view('employees.edit', [
            'employee' => $employee,
            'bloodGroups' => BloodGroup::cases(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee, EmployeeService $employees): RedirectResponse
    {
        $employees->update($employee, $request->validated());

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', __('Employee updated.'));
    }

    public function deactivate(Employee $employee, EmployeeService $employees): RedirectResponse
    {
        $this->authorize('deactivate', $employee);
        $employees->deactivate($employee);

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', __('Employee deactivated. Future logins and current sessions are blocked.'));
    }

    public function reactivate(Employee $employee, EmployeeService $employees): RedirectResponse
    {
        $this->authorize('reactivate', $employee);
        $employees->reactivate($employee);

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', __('Employee reactivated.'));
    }

    public function resetPassword(ResetEmployeePasswordRequest $request, Employee $employee, EmployeeService $employees): RedirectResponse
    {
        $employees->resetPassword($employee, $request->validated('password'));

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', __('Temporary password set. The employee must change it on next sign-in. The password is not stored in plaintext.'));
    }

    public function photo(Employee $employee, EmployeePhotoService $photos)
    {
        $this->authorize('viewPhoto', $employee);

        return $photos->stream($employee);
    }
}
