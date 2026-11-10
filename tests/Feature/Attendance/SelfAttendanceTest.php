<?php

namespace Tests\Feature\Attendance;

use App\Attendance\AttendanceService;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SelfAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function activeEmployee(): Employee
    {
        return Employee::factory()->activeAccount()->create();
    }

    public function test_employee_can_check_in_and_check_out_with_server_timestamps(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-10 03:15:00', 'UTC'));
        $employee = $this->activeEmployee();

        $this->actingAs($employee->user)
            ->post(route('attendance.check-in'))
            ->assertRedirect(route('attendance.mine'));

        $record = AttendanceRecord::query()->sole();
        $this->assertSame('2026-11-10', $record->work_date->toDateString());
        $this->assertTrue($record->check_in_at->equalTo(CarbonImmutable::parse('2026-11-10 03:15:00', 'UTC')));
        $this->assertNull($record->check_out_at);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-10 12:00:00', 'UTC'));

        $this->actingAs($employee->user)
            ->post(route('attendance.check-out'))
            ->assertRedirect(route('attendance.mine'));

        $record->refresh();
        $this->assertTrue($record->check_out_at->equalTo(CarbonImmutable::parse('2026-11-10 12:00:00', 'UTC')));
        $this->assertSame('2026-11-10', $record->work_date->toDateString());
    }

    public function test_duplicate_check_in_is_rejected(): void
    {
        $employee = $this->activeEmployee();
        $this->actingAs($employee->user)->post(route('attendance.check-in'))->assertRedirect();

        $this->actingAs($employee->user)
            ->from(route('attendance.mine'))
            ->post(route('attendance.check-in'))
            ->assertRedirect(route('attendance.mine'))
            ->assertSessionHasErrors('attendance');

        $this->assertDatabaseCount('attendance_records', 1);
    }

    public function test_checkout_without_check_in_is_rejected(): void
    {
        $employee = $this->activeEmployee();

        $this->actingAs($employee->user)
            ->from(route('attendance.mine'))
            ->post(route('attendance.check-out'))
            ->assertRedirect(route('attendance.mine'))
            ->assertSessionHasErrors('attendance');
    }

    public function test_duplicate_checkout_is_rejected(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-10 03:00:00', 'UTC'));
        $employee = $this->activeEmployee();
        $this->actingAs($employee->user)->post(route('attendance.check-in'));
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-10 11:00:00', 'UTC'));
        $this->actingAs($employee->user)->post(route('attendance.check-out'));

        $this->actingAs($employee->user)
            ->from(route('attendance.mine'))
            ->post(route('attendance.check-out'))
            ->assertSessionHasErrors('attendance');
    }

    public function test_overnight_checkout_keeps_original_work_date(): void
    {
        // 22:00 Asia/Dhaka = 16:00 UTC same calendar day in Nov (UTC+6)
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-10 16:00:00', 'UTC'));
        $employee = $this->activeEmployee();
        $this->actingAs($employee->user)->post(route('attendance.check-in'));

        // 02:00 next local day = 20:00 UTC same UTC day
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-10 20:00:00', 'UTC'));
        $this->actingAs($employee->user)->post(route('attendance.check-out'))->assertRedirect();

        $record = AttendanceRecord::query()->sole();
        $this->assertSame('2026-11-10', $record->work_date->toDateString());
        $this->assertNotNull($record->check_out_at);
        $service = app(AttendanceService::class);
        $this->assertSame('2026-11-10 22:00', $service->formatLocal($record->check_in_at));
        $this->assertSame('2026-11-11 02:00', $service->formatLocal($record->check_out_at));
    }

    public function test_second_day_check_in_rejected_while_earlier_entry_open(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-10 10:00:00', 'UTC'));
        $employee = $this->activeEmployee();
        $this->actingAs($employee->user)->post(route('attendance.check-in'));

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-11 10:00:00', 'UTC'));
        $this->actingAs($employee->user)
            ->from(route('attendance.mine'))
            ->post(route('attendance.check-in'))
            ->assertSessionHasErrors('attendance');

        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertTrue(AttendanceRecord::query()->sole()->isOpen());
    }

    public function test_concurrent_check_in_only_creates_one_record(): void
    {
        $employee = $this->activeEmployee();
        $service = app(AttendanceService::class);
        $created = 0;
        $failures = 0;

        foreach (range(1, 5) as $attempt) {
            try {
                DB::transaction(function () use ($service, $employee, &$created) {
                    $service->checkIn($employee, $employee->user);
                    $created++;
                });
            } catch (ValidationException) {
                $failures++;
            }
        }

        $this->assertSame(1, $created);
        $this->assertSame(4, $failures);
        $this->assertDatabaseCount('attendance_records', 1);
    }

    public function test_inactive_employee_cannot_punch(): void
    {
        $employee = Employee::factory()->inactive()->create();

        $this->actingAs($employee->user)
            ->post(route('attendance.check-in'))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_employee_cannot_access_another_employees_attendance_or_admin_list(): void
    {
        $viewer = $this->activeEmployee();
        $other = $this->activeEmployee();
        AttendanceRecord::factory()->create([
            'employee_id' => $other->id,
            'created_by' => $other->user_id,
            'updated_by' => $other->user_id,
        ]);

        $this->actingAs($viewer->user)
            ->get(route('attendance.index'))
            ->assertForbidden();

        $this->actingAs($viewer->user)
            ->get(route('attendance.mine'))
            ->assertOk()
            ->assertDontSee($other->user->name, false);
    }

    public function test_super_user_without_employee_profile_cannot_self_punch_routes(): void
    {
        $admin = User::factory()->superUser()->create(['must_change_password' => false]);

        $this->actingAs($admin)
            ->get(route('attendance.mine'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('attendance.check-in'))
            ->assertNotFound();
    }

    public function test_timezone_conversion_uses_configured_business_timezone(): void
    {
        config(['hrms.display_timezone' => 'Asia/Dhaka']);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-10 18:30:00', 'UTC'));
        $employee = $this->activeEmployee();

        $this->actingAs($employee->user)->post(route('attendance.check-in'));
        $record = AttendanceRecord::query()->sole();

        $this->assertSame('2026-11-11', $record->work_date->toDateString());
        $this->assertSame('2026-11-11 00:30', app(AttendanceService::class)->formatLocal($record->check_in_at));
    }

    public function test_must_change_password_blocks_attendance_pages(): void
    {
        $employee = Employee::factory()->mustChangePassword()->create();

        $this->actingAs($employee->user)
            ->get(route('attendance.mine'))
            ->assertRedirect(route('password.forced.edit'));
    }
}
