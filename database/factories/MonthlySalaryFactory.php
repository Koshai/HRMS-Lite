<?php

namespace Database\Factories;

use App\Enums\SalaryStatus;
use App\Models\Employee;
use App\Models\MonthlySalary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonthlySalary>
 */
class MonthlySalaryFactory extends Factory
{
    protected $model = MonthlySalary::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory()->activeAccount(),
            'salary_month' => now()->startOfMonth()->toDateString(),
            'salary_amount' => '50000.00',
            'given_status' => SalaryStatus::Unpaid,
            'has_bonus' => false,
            'bonus_amount' => '0.00',
            'paid_at' => null,
            'paid_by' => null,
            'version' => 0,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'given_status' => SalaryStatus::Paid,
            'paid_at' => now(),
            'paid_by' => User::factory()->superUser(),
        ]);
    }
}
