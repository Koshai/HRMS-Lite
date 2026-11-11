<?php

namespace Tests\Feature;

use App\Enums\SalaryStatus;
use App\Models\AttendanceRecord;
use App\Models\AuditEvent;
use App\Models\Employee;
use App\Models\MonthlySalary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PDO;
use Tests\TestCase;

/**
 * Disposable SQLite backup/restore drill (A24).
 * Never point this at a production database.
 */
class BackupRestoreDrillTest extends TestCase
{
    use RefreshDatabase;

    public function test_sqlite_backup_and_restore_retains_history_and_private_photos(): void
    {
        Storage::fake('local');

        $admin = User::factory()->superUser()->create([
            'email' => 'restore-admin@example.test',
            'name' => 'Restore Admin',
        ]);

        $employee = Employee::factory()->activeAccount()->create([
            'designation' => 'Restore Clerk',
            'base_salary_amount' => '42000.00',
            'image_path' => 'employees/restore-photo.bin',
        ]);

        Storage::disk('local')->put('employees/restore-photo.bin', 'synthetic-photo-bytes');

        AttendanceRecord::factory()->closed()->create([
            'employee_id' => $employee->id,
            'created_by' => $admin->id,
        ]);

        $salary = MonthlySalary::factory()->paid()->create([
            'employee_id' => $employee->id,
            'salary_month' => '2026-10-01',
            'salary_amount' => '42000.00',
            'bonus_amount' => '1000.00',
            'has_bonus' => true,
            'paid_by' => $admin->id,
        ]);

        AuditEvent::query()->create([
            'actor_user_id' => $admin->id,
            'entity_type' => 'monthly_salary',
            'entity_id' => $salary->id,
            'action' => 'salary.mark_paid',
            'before_json' => ['given_status' => SalaryStatus::Unpaid->value],
            'after_json' => ['given_status' => SalaryStatus::Paid->value],
            'reason' => 'restore-drill',
            'created_at' => now(),
        ]);

        $backupDb = database_path('testing-backup-drill.sqlite');
        $photoBackup = storage_path('app/backup-drill-private/restore-photo.bin');
        File::delete($backupDb);
        File::ensureDirectoryExists(dirname($photoBackup));

        $source = DB::connection()->getPdo();
        $restore = new PDO('sqlite:'.$backupDb);
        $restore->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $schemaRows = $source->query(
            "SELECT type, name, sql FROM sqlite_master WHERE sql IS NOT NULL AND name NOT LIKE 'sqlite_%' ORDER BY CASE type WHEN 'table' THEN 0 ELSE 1 END"
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($schemaRows as $row) {
            $restore->exec($row['sql']);
        }

        $tables = $source->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"
        )->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $rows = DB::table($table)->get();
            if ($rows->isEmpty()) {
                continue;
            }

            foreach ($rows as $row) {
                $data = (array) $row;
                $columns = array_keys($data);
                $placeholders = implode(',', array_fill(0, count($columns), '?'));
                $columnList = implode(',', array_map(fn ($c) => '"'.$c.'"', $columns));
                $stmt = $restore->prepare("INSERT INTO \"{$table}\" ({$columnList}) VALUES ({$placeholders})");
                $stmt->execute(array_map(
                    fn ($value) => is_bool($value) ? (int) $value : $value,
                    array_values($data)
                ));
            }
        }

        File::put($photoBackup, Storage::disk('local')->get('employees/restore-photo.bin'));

        $userCount = (int) $restore->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $employeeCount = (int) $restore->query('SELECT COUNT(*) FROM employees')->fetchColumn();
        $attendanceCount = (int) $restore->query('SELECT COUNT(*) FROM attendance_records')->fetchColumn();
        $salaryCount = (int) $restore->query('SELECT COUNT(*) FROM monthly_salaries')->fetchColumn();
        $auditCount = (int) $restore->query('SELECT COUNT(*) FROM audit_events')->fetchColumn();

        $this->assertGreaterThanOrEqual(2, $userCount);
        $this->assertSame(1, $employeeCount);
        $this->assertSame(1, $attendanceCount);
        $this->assertSame(1, $salaryCount);
        $this->assertGreaterThanOrEqual(1, $auditCount);

        $restoredSalary = $restore->query('SELECT * FROM monthly_salaries WHERE id = '.(int) $salary->id)->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(SalaryStatus::Paid->value, $restoredSalary['given_status']);
        $this->assertSame(0, bccomp((string) $restoredSalary['salary_amount'], '42000.00', 2));

        $restoredAudit = $restore->query("SELECT * FROM audit_events WHERE action = 'salary.mark_paid'")->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($restoredAudit);
        $this->assertSame('restore-drill', $restoredAudit['reason']);

        $this->assertSame('synthetic-photo-bytes', File::get($photoBackup));
    }

    protected function tearDown(): void
    {
        File::delete(database_path('testing-backup-drill.sqlite'));
        File::deleteDirectory(storage_path('app/backup-drill-private'));

        parent::tearDown();
    }
}
