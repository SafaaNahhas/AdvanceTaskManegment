<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class StoreTaskRequestTest extends TestCase
{

// Use DatabaseTransactions to roll back changes after each test
use DatabaseTransactions;
protected $user;

protected function setUp(): void
{
    parent::setUp();

    // Create a user with the necessary permissions
    $this->user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'admin']);
    $permission = Permission::firstOrCreate(['name' => 'store task']);
    $role->givePermissionTo($permission);
    $this->user->assignRole($role);

    Task::factory()->create(['title' => 'Duplicate Title']);

}


/**
 * @dataProvider invalidFields
 */
public function test_validation_rules($field, $value, $error)
{
    $this->actingAs($this->user, 'api');

    $data = [
        'title' => 'Valid Title',
        'type' => 'Bug',
        'priority' => 'Medium',
        'due_date' => '2024-12-01',
        'assigned_to' => null,
        'dependencies' => []
    ];
    if ($field === 'dependencies.0') {
        $data['dependencies'] = [$value];
        $field = 'dependencies.0';
    } else {
        $data[$field] = $value;
    }

        $response = $this->postJson('/api/tasks', $data);

        $decodedResponse = json_decode($response->getContent(), true);

        Log::debug('Decoded Response: ', ['response' => $decodedResponse]);

        $response->assertStatus(422)
            ->assertJson([
                'details' => [
                    $field => [$error]
                ]
            ]);
    }

    public static function invalidFields()
      {
        return [
            'Null title' => ['title', null, 'The title field is required.'],
            'Duplicate title' => ['title', 'Duplicate Title', 'The title must be unique.'],
            'Invalid title characters' => ['title', '@Invalid$', 'The title contains invalid characters.'],
            'Invalid type' => ['type', 'Invalid', 'The selected type is invalid. It must be Bug, Feature, or Improvement.'],
            'Null type' => ['type', null, 'The task type is required.'],
            'Invalid priority' => ['priority', 'Invalid', 'The selected priority is invalid. It must be Low, Medium, or High.'],
            'Null priority' => ['priority', null, 'The task priority is required.'],
            'Invalid due_date' => ['due_date', 'invalid_date', 'The due date must be a valid date.'],
            'Past due_date' => ['due_date', '2023-10-10', 'The due date must be today or in the future.'],
            'Null due_date' => ['due_date', null, 'The due date is required.'],
            'Invalid assigned_to' => ['assigned_to', 99999, 'The selected user does not exist.'],
            'Dependencies not array' => ['dependencies', 'not_an_array', 'The dependencies must be an array.'],
            'Invalid dependency ID' => ['dependencies.0', 99999, 'One of the selected dependencies does not exist.'],
        ];
    }

}
