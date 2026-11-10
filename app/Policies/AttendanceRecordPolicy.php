<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
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

    public function view(User $actor, AttendanceRecord $record): bool
    {
        if (! $actor->is_active) {
            return false;
        }

        if ($actor->isSuperUser()) {
            return true;
        }

        return $actor->isEmployee()
            && $actor->employee !== null
            && $record->employee_id === $actor->employee->id;
    }

    public function manage(User $actor): bool
    {
        return $actor->isSuperUser() && $actor->is_active && ! $actor->mustChangePassword();
    }

    public function create(User $actor): bool
    {
        return $this->manage($actor);
    }

    public function update(User $actor, AttendanceRecord $record): bool
    {
        return $this->manage($actor);
    }
}
