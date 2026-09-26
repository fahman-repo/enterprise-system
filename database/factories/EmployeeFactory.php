<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'manager_id' => null,
            'employee_number' => strtoupper(fake()->unique()->bothify('EMP-#####')),
            'name' => fake()->name(),
            'gender' => fake()->randomElement(Employee::GENDERS),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-55 years', '-18 years')->format('Y-m-d'),
            'religion_id' => null,
            'marital_status_id' => null,
            'education_level_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('08##########'),
            'identity_number' => fake()->unique()->numerify('################'),
            'npwp' => null,
            'bpjs_kesehatan' => null,
            'bpjs_ketenagakerjaan' => null,
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_relationship' => fake()->randomElement(['Spouse', 'Parent', 'Sibling']),
            'emergency_contact_phone' => fake()->numerify('08##########'),
            'bank_name' => fake()->randomElement(['BCA', 'BNI', 'BRI', 'Mandiri']),
            'bank_account_number' => fake()->unique()->numerify('##########'),
            'bank_account_name' => fake()->name(),
            'division_id' => null,
            'department_id' => null,
            'org_unit_id' => null,
            'position_id' => null,
            'grade_id' => null,
            'site_id' => null,
            'employment_status_id' => null,
            'join_date' => fake()->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
            'end_date' => null,
            'probation_end_date' => null,
            'photo_path' => null,
            'is_active' => true,
        ];
    }

    /**
     * An inactive employee (no longer with the company).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Place the employee in the given department and its division.
     */
    public function forDepartment(Department $department): static
    {
        return $this->state(fn (array $attributes) => [
            'division_id' => $department->division_id,
            'department_id' => $department->id,
        ]);
    }

    /**
     * Put the employee under the given manager in the reporting line.
     */
    public function reportsTo(Employee $manager): static
    {
        return $this->state(fn (array $attributes) => [
            'manager_id' => $manager->id,
        ]);
    }
}
