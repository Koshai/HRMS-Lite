<?php

namespace Database\Factories;

use App\Enums\BloodGroup;
use App\Enums\Role;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state([
                'role' => Role::Employee,
                'must_change_password' => true,
            ]),
            'phone' => fake()->optional()->numerify('01#########'),
            'address' => fake()->optional()->address(),
            'blood_group' => fake()->optional()->randomElement(BloodGroup::cases()),
            'image_path' => null,
            'designation' => fake()->jobTitle(),
            'base_salary_amount' => fake()->randomFloat(2, 15000, 120000),
            'is_active' => true,
        ];
    }

    public function activeAccount(): static
    {
        return $this->afterCreating(function (Employee $employee): void {
            $employee->user->forceFill([
                'is_active' => true,
                'must_change_password' => false,
            ])->save();
            $employee->forceFill(['is_active' => true])->save();
        });
    }

    public function inactive(): static
    {
        return $this->afterCreating(function (Employee $employee): void {
            $employee->user->forceFill(['is_active' => false])->save();
            $employee->forceFill(['is_active' => false])->save();
        });
    }

    public function mustChangePassword(): static
    {
        return $this->afterCreating(function (Employee $employee): void {
            $employee->user->forceFill(['must_change_password' => true])->save();
        });
    }
}
