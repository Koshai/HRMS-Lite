<?php

namespace Tests\Feature\Salary;

use App\Enums\SalaryStatus;
use App\Models\AuditEvent;
use App\Models\Employee;
use App\Models\MonthlySalary;
use App\Models\User;
use App\Salary\SalaryService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SalaryPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->superUser()->create(['must_change_password' => false]);
    }

    private function seedUnpaid(User $admin, ?Employee $employee = null): MonthlySalary
    {
        $employee ??= Employee::factory()->activeAccount()->create(['base_salary_amount' => '50000.00']);

        return app(SalaryService::class)->create($employee, $admin, [
            'salary_month' => '2026-11',
            'reason' => 'Seed unpaid',
        ]);
    }

    public function test_mark_paid_sets_timestamp_actor_and_audit(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-15 10:30:00', 'UTC'));
        $admin = $this->admin();
        $record = $this->seedUnpaid($admin);

        $this->actingAs($admin)
            ->post(route('salaries.mark-paid', $record), [
                'expected_version' => 0,
            ])
            ->assertRedirect(route('salaries.show', $record));

        $record->refresh();
        $this->assertSame(SalaryStatus::Paid, $record->given_status);
        $this->assertTrue($record->paid_at->equalTo(CarbonImmutable::parse('2026-11-15 10:30:00', 'UTC')));
        $this->assertSame($admin->id, $record->paid_by);
        $this->assertSame(1, $record->version);

        $paidAudit = AuditEvent::query()->where('action', 'salary.mark_paid')->sole();
        $this->assertSame($admin->id, $paidAudit->actor_user_id);
        $this->assertSame('unpaid', $paidAudit->before_json['given_status']);
        $this->assertSame('paid', $paidAudit->after_json['given_status']);
    }

    public function test_repeated_mark_paid_is_idempotent(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-15 10:30:00', 'UTC'));
        $admin = $this->admin();
        $service = app(SalaryService::class);
        $record = $this->seedUnpaid($admin);

        $service->markPaid($record, $admin, 0);
        $originalPaidAt = $record->fresh()->paid_at;

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-15 12:00:00', 'UTC'));
        $again = $service->markPaid($record->fresh(), $admin, 0);

        $this->assertTrue($again->paid_at->equalTo($originalPaidAt));
        $this->assertSame($admin->id, $again->paid_by);
        $this->assertSame(1, AuditEvent::query()->where('action', 'salary.mark_paid')->count());
        $this->assertSame(1, $again->version);
    }

    public function test_reversal_requires_reason_and_preserves_prior_paid_in_audit(): void
    {
        $admin = $this->admin();
        $service = app(SalaryService::class);
        $record = $this->seedUnpaid($admin);
        $service->markPaid($record, $admin, 0);

        $this->actingAs($admin)
            ->post(route('salaries.reverse-unpaid', $record->fresh()), [
                'reason' => '',
                'expected_version' => 1,
            ])
            ->assertSessionHasErrors('reason');

        $this->actingAs($admin)
            ->post(route('salaries.reverse-unpaid', $record->fresh()), [
                'reason' => 'Payment bounced / entry error',
                'expected_version' => 1,
            ])
            ->assertRedirect(route('salaries.show', $record));

        $record->refresh();
        $this->assertSame(SalaryStatus::Unpaid, $record->given_status);
        $this->assertNull($record->paid_at);
        $this->assertNull($record->paid_by);

        $reversal = AuditEvent::query()->where('action', 'salary.reverse_unpaid')->sole();
        $this->assertSame('paid', $reversal->before_json['given_status']);
        $this->assertNotNull($reversal->before_json['paid_at']);
        $this->assertSame($admin->id, $reversal->before_json['paid_by']);
        $this->assertSame('unpaid', $reversal->after_json['given_status']);
        $this->assertSame('Payment bounced / entry error', $reversal->reason);
        $this->assertSame(1, AuditEvent::query()->where('action', 'salary.mark_paid')->count());
    }

    public function test_stale_version_rejected_for_payment_changes(): void
    {
        $admin = $this->admin();
        $service = app(SalaryService::class);
        $record = $this->seedUnpaid($admin);

        $service->markPaid($record, $admin, 0);

        $this->expectException(ValidationException::class);
        $service->reverseToUnpaid($record->fresh(), $admin, [
            'reason' => 'Stale reverse',
            'expected_version' => 0,
        ]);
    }

    public function test_concurrent_style_second_mark_paid_after_version_bump_is_idempotent(): void
    {
        $admin = $this->admin();
        $service = app(SalaryService::class);
        $record = $this->seedUnpaid($admin);

        $first = $service->markPaid($record, $admin, 0);
        $second = $service->markPaid($record->fresh(), $admin, 0);

        $this->assertTrue($first->paid_at->equalTo($second->paid_at));
        $this->assertSame(1, AuditEvent::query()->where('action', 'salary.mark_paid')->count());
    }

    public function test_unauthorized_payment_actions_do_not_modify_data(): void
    {
        $admin = $this->admin();
        $employee = Employee::factory()->activeAccount()->create(['base_salary_amount' => '50000.00']);
        $record = $this->seedUnpaid($admin, $employee);

        $this->actingAs($employee->user)
            ->post(route('salaries.mark-paid', $record), ['expected_version' => 0])
            ->assertForbidden();

        $this->assertSame(SalaryStatus::Unpaid, $record->fresh()->given_status);
        $this->assertSame(0, AuditEvent::query()->where('action', 'salary.mark_paid')->count());
    }

    public function test_paid_record_amounts_cannot_be_silently_edited(): void
    {
        $admin = $this->admin();
        $service = app(SalaryService::class);
        $record = $this->seedUnpaid($admin);
        $service->markPaid($record, $admin, 0);

        $this->actingAs($admin)
            ->put(route('salaries.update', $record->fresh()), [
                'salary_amount' => '99999.00',
                'reason' => 'Should fail',
                'expected_version' => 1,
            ])
            ->assertSessionHasErrors('salary');

        $this->assertSame('50000.00', (string) $record->fresh()->salary_amount);
    }

    public function test_failed_reversal_does_not_write_partial_audit(): void
    {
        $admin = $this->admin();
        $service = app(SalaryService::class);
        $record = $this->seedUnpaid($admin);
        $service->markPaid($record, $admin, 0);
        $auditsBefore = AuditEvent::query()->count();

        try {
            $service->reverseToUnpaid($record->fresh(), $admin, [
                'reason' => '',
                'expected_version' => 1,
            ]);
            $this->fail('Expected validation');
        } catch (ValidationException) {
            // expected
        }

        $this->assertSame(SalaryStatus::Paid, $record->fresh()->given_status);
        $this->assertSame($auditsBefore, AuditEvent::query()->count());
    }
}
