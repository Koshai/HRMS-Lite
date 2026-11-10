<?php

namespace Tests\Feature\Attendance;

use App\Attendance\AttendanceService;
use App\Models\AttendanceRecord;
use App\Models\AuditEvent;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superUser()->create(['must_change_password' => false]);
    }

    private function employee(): Employee
    {
        return Employee::factory()->activeAccount()->create();
    }

    public function test_admin_can_create_manual_attendance_with_audit(): void
    {
        $admin = $this->admin();
        $employee = $this->employee();

        $this->actingAs($admin)
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'work_date' => '2026-11-10',
                'check_in_time' => '09:00',
                'check_out_time' => '17:30',
                'reason' => 'Missed punch entered from timesheet',
            ])
            ->assertRedirect();

        $record = AttendanceRecord::query()->sole();
        $this->assertSame('2026-11-10', $record->work_date->toDateString());
        $this->assertSame('2026-11-10 09:00', app(AttendanceService::class)->formatLocal($record->check_in_at));
        $this->assertSame('2026-11-10 17:30', app(AttendanceService::class)->formatLocal($record->check_out_at));

        $audit = AuditEvent::query()->sole();
        $this->assertSame($admin->id, $audit->actor_user_id);
        $this->assertSame('attendance.manual_create', $audit->action);
        $this->assertSame('Missed punch entered from timesheet', $audit->reason);
        $this->assertNull($audit->before_json);
        $this->assertSame($record->id, $audit->entity_id);
    }

    public function test_admin_can_correct_attendance_preserving_work_date_and_audit_trail(): void
    {
        $admin = $this->admin();
        $employee = $this->employee();
        $service = app(AttendanceService::class);

        $record = $service->createManual($employee, $admin, [
            'work_date' => '2026-11-10',
            'check_in_time' => '09:00',
            'check_out_time' => '17:00',
            'reason' => 'Initial entry',
        ]);

        $this->actingAs($admin)
            ->put(route('attendance.update', $record), [
                'check_in_time' => '09:15',
                'check_out_time' => '18:00',
                'reason' => 'Corrected from gate log',
                'expected_check_in_at' => $record->fresh()->check_in_at->toIso8601String(),
                'expected_check_out_at' => $record->fresh()->check_out_at?->toIso8601String(),
            ])
            ->assertRedirect(route('attendance.show', $record));

        $record->refresh();
        $this->assertSame('2026-11-10', $record->work_date->toDateString());
        $this->assertSame('2026-11-10 09:15', $service->formatLocal($record->check_in_at));
        $this->assertSame('2026-11-10 18:00', $service->formatLocal($record->check_out_at));

        $correction = AuditEvent::query()->where('action', 'attendance.manual_correct')->sole();
        $this->assertSame('Corrected from gate log', $correction->reason);
        $this->assertNotNull($correction->before_json);
        $this->assertNotNull($correction->after_json);
        $this->assertNotSame($correction->before_json['check_in_at'], $correction->after_json['check_in_at']);
    }

    public function test_reason_is_mandatory_for_manual_entry_and_correction(): void
    {
        $admin = $this->admin();
        $employee = $this->employee();

        $this->actingAs($admin)
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'work_date' => '2026-11-10',
                'check_in_time' => '09:00',
                'reason' => '',
            ])
            ->assertSessionHasErrors('reason');

        $record = app(AttendanceService::class)->createManual($employee, $admin, [
            'work_date' => '2026-11-10',
            'check_in_time' => '09:00',
            'reason' => 'Seed',
        ]);

        $this->actingAs($admin)
            ->put(route('attendance.update', $record), [
                'check_in_time' => '09:30',
                'reason' => '',
                'expected_check_in_at' => $record->check_in_at->toIso8601String(),
                'expected_check_out_at' => $record->check_out_at?->toIso8601String(),
            ])
            ->assertSessionHasErrors('reason');
    }

    public function test_employee_cannot_manually_edit_attendance(): void
    {
        $admin = $this->admin();
        $employee = $this->employee();
        $record = app(AttendanceService::class)->createManual($employee, $admin, [
            'work_date' => '2026-11-10',
            'check_in_time' => '09:00',
            'reason' => 'Seed',
        ]);

        $this->actingAs($employee->user)
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'work_date' => '2026-11-11',
                'check_in_time' => '09:00',
                'reason' => 'Should fail',
            ])
            ->assertForbidden();

        $this->actingAs($employee->user)
            ->put(route('attendance.update', $record), [
                'check_in_time' => '10:00',
                'reason' => 'Should fail',
                'expected_check_in_at' => $record->check_in_at->toIso8601String(),
                'expected_check_out_at' => $record->check_out_at?->toIso8601String(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertSame(1, AuditEvent::query()->count());
    }

    public function test_invalid_time_ranges_and_duplicates_are_rejected(): void
    {
        $admin = $this->admin();
        $employee = $this->employee();

        $this->actingAs($admin)
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'work_date' => '2026-11-10',
                'check_in_time' => '18:00',
                'check_out_time' => '09:00',
                'reason' => 'Bad range',
            ])
            ->assertSessionHasErrors('check_out_time');

        app(AttendanceService::class)->createManual($employee, $admin, [
            'work_date' => '2026-11-10',
            'check_in_time' => '09:00',
            'reason' => 'First',
        ]);

        $this->actingAs($admin)
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'work_date' => '2026-11-10',
                'check_in_time' => '10:00',
                'reason' => 'Duplicate',
            ])
            ->assertSessionHasErrors('work_date');
    }

    public function test_overnight_manual_attendance_uses_explicit_next_day_flag(): void
    {
        $admin = $this->admin();
        $employee = $this->employee();
        $service = app(AttendanceService::class);

        $record = $service->createManual($employee, $admin, [
            'work_date' => '2026-11-10',
            'check_in_time' => '22:00',
            'check_out_time' => '06:00',
            'checkout_next_day' => true,
            'reason' => 'Night shift',
        ]);

        $this->assertSame('2026-11-10', $record->work_date->toDateString());
        $this->assertSame('2026-11-10 22:00', $service->formatLocal($record->check_in_at));
        $this->assertSame('2026-11-11 06:00', $service->formatLocal($record->check_out_at));
    }

    public function test_concurrent_corrections_do_not_silently_overwrite(): void
    {
        $admin = $this->admin();
        $employee = $this->employee();
        $service = app(AttendanceService::class);

        $record = $service->createManual($employee, $admin, [
            'work_date' => '2026-11-10',
            'check_in_time' => '09:00',
            'check_out_time' => '17:00',
            'reason' => 'Seed',
        ]);

        $expectedIn = $record->check_in_at->toIso8601String();
        $expectedOut = $record->check_out_at?->toIso8601String();

        $service->correctManual($record, $admin, [
            'check_in_time' => '09:05',
            'check_out_time' => '17:05',
            'reason' => 'First correction',
            'expected_check_in_at' => $expectedIn,
            'expected_check_out_at' => $expectedOut,
        ]);

        $this->expectException(ValidationException::class);

        $service->correctManual($record->fresh(), $admin, [
            'check_in_time' => '09:10',
            'check_out_time' => '17:10',
            'reason' => 'Stale correction',
            'expected_check_in_at' => $expectedIn,
            'expected_check_out_at' => $expectedOut,
        ]);
    }

    public function test_unauthorized_requests_do_not_modify_data(): void
    {
        $employee = $this->employee();
        $before = AttendanceRecord::query()->count();

        $this->post(route('attendance.store'), [
            'employee_id' => $employee->id,
            'work_date' => '2026-11-10',
            'check_in_time' => '09:00',
            'reason' => 'Anon',
        ])->assertRedirect('/login');

        $this->assertSame($before, AttendanceRecord::query()->count());
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_max_duration_is_enforced_for_manual_entry(): void
    {
        config(['hrms.attendance_max_hours' => 8]);
        $admin = $this->admin();
        $employee = $this->employee();

        $this->actingAs($admin)
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'work_date' => '2026-11-10',
                'check_in_time' => '09:00',
                'check_out_time' => '20:00',
                'reason' => 'Too long',
            ])
            ->assertSessionHasErrors('check_out_time');
    }
}
