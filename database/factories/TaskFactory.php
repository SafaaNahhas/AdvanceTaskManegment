<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'due_date' => $this->faker->date,
            'status' => $this->faker->randomElement(['Open', 'In Progress', 'Completed', 'Blocked']), // اختيار حالة عشوائية
            'type' => $this->faker->randomElement(['Bug', 'Feature', 'Improvement']),            // 'assigned_to' => 1, // Adjust as needed
        ];
    }
}
