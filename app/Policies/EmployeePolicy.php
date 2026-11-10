<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isSuperUser() && $actor->is_active;
    }

    public function view(User $actor, Employee $employee): bool
    {
        if (! $actor->is_active) {
            return false;
        }

        if ($actor->isSuperUser()) {
            return true;
        }

        return $actor->isEmployee() && $employee->user_id === $actor->id;
    }

    public function create(User $actor): bool
    {
        return $actor->isSuperUser() && $actor->is_active;
    }

    public function update(User $actor, Employee $employee): bool
    {
        return $actor->isSuperUser() && $actor->is_active;
    }

    public function deactivate(User $actor, Employee $employee): bool
    {
        return $actor->isSuperUser()
            && $actor->is_active
            && $employee->user_id !== $actor->id;
    }

    public function reactivate(User $actor, Employee $employee): bool
    {
        return $this->deactivate($actor, $employee);
    }

    public function resetPassword(User $actor, Employee $employee): bool
    {
        return $actor->isSuperUser()
            && $actor->is_active
            && $employee->user_id !== $actor->id;
    }

    public function viewSensitive(User $actor, Employee $employee): bool
    {
        return $this->view($actor, $employee)
            && ($actor->isSuperUser() || $employee->user_id === $actor->id);
    }

    public function viewPhoto(User $actor, Employee $employee): bool
    {
        return $this->view($actor, $employee);
    }
}
