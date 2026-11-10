<?php

namespace App\Http\Controllers;

use App\Employees\EmployeePhotoService;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyProfileController extends Controller
{
    public function show(Request $request): View
    {
        $employee = $request->user()?->employee;

        abort_unless($employee instanceof Employee, 404);
        $this->authorize('view', $employee);

        return view('employees.mine', [
            'employee' => $employee->loadMissing('user'),
            'canViewSensitive' => true,
        ]);
    }

    public function photo(Request $request, EmployeePhotoService $photos)
    {
        $employee = $request->user()?->employee;

        abort_unless($employee instanceof Employee, 404);
        $this->authorize('viewPhoto', $employee);

        return $photos->stream($employee);
    }
}
