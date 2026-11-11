<?php

namespace App\Policies;

use App\Models\MonthlySalary;
use App\Models\User;

class MonthlySalaryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->is_active && ($actor->isSuperUser() || $actor->isEmployee());
    }

    public function viewOwn(User $actor): bool
    {
        return $actor->is_active
            && $actor->isEmployee()
            && $actor->employee !== null
            && ! $actor->mustChangePassword();
    }

    public function view(User $actor, MonthlySalary $salary): bool
    {
        if (! $actor->is_active) {
            return false;
        }

        if ($actor->isSuperUser()) {
            return true;
        }

        return $actor->isEmployee()
            && $actor->employee !== null
            && $salary->employee_id === $actor->employee->id;
    }

    public function manage(User $actor): bool
    {
        return $actor->isSuperUser() && $actor->is_active && ! $actor->mustChangePassword();
    }

    public function create(User $actor): bool
    {
        return $this->manage($actor);
    }

    public function update(User $actor, MonthlySalary $salary): bool
    {
        return $this->manage($actor);
    }

    public function markPaid(User $actor, MonthlySalary $salary): bool
    {
        return $this->manage($actor);
    }

    public function reverseUnpaid(User $actor, MonthlySalary $salary): bool
    {
        return $this->manage($actor);
    }
}
