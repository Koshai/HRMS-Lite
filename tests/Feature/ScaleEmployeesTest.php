<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScaleEmployeesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_and_employee_index_remain_usable_with_fifty_synthetic_employees(): void
    {
        $admin = User::factory()->superUser()->create();

        Employee::factory()
            ->count(50)
            ->activeAccount()
            ->sequence(fn ($sequence) => [
                'designation' => 'Staff '.$sequence->index,
            ])
            ->create();

        $this->assertSame(50, Employee::query()->where('is_active', true)->count());

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('50')
            ->assertSee('Active employees');

        $this->actingAs($admin)
            ->get(route('employees.index'))
            ->assertOk();

        // Spot-check that history-preserving deactivation still works at scale sample.
        $sample = Employee::query()->firstOrFail();
        $sample->forceFill(['is_active' => false])->save();
        $sample->user->forceFill(['is_active' => false])->save();

        $this->assertSame(49, Employee::query()->where('is_active', true)->count());
        $this->assertDatabaseHas('employees', ['id' => $sample->id, 'designation' => $sample->designation]);
    }
}
