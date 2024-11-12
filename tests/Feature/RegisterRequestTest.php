<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\WithFaker;
use App\Http\Requests\AuthRequest\RegisterRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RegisterRequestTest extends TestCase
{
    // Use DatabaseTransactions to roll back changes after each test
    use DatabaseTransactions;

    public function test_register_request_with_valid_data()
    {
        $data = [
            'name' => 'Valid User',
            'email' => 'validuser@example.com',
            'password' => 'securepassword',
        ];

        $request = new RegisterRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes(), 'Validation should pass with valid data.');
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function test_register_request_with_invalid_data($field, $value, $expectedError)
{
    if ($field === 'email' && $value === 'existinguser@example.com') {
        \App\Models\User::factory()->create(['email' => 'existinguser@example.com']);
    }

    $request = new RegisterRequest();
    $data = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => '123456',
    ];

    $data[$field] = $value;

    $validator = Validator::make($data, $request->rules());

    $this->assertFalse($validator->passes(), "Validation should fail for invalid $field.");
    $this->assertArrayHasKey($field, $validator->errors()->toArray(), "Error message should exist for $field.");
    $this->assertEquals($expectedError, $validator->errors()->first($field));
}


    public static function invalidDataProvider()
{
    return [
        'Missing name' => ['name', null, 'The name field is required.'],
        'Missing email' => ['email', null, 'The email field is required.'],
        'Invalid email' => ['email', 'invalid-email', 'The email field must be a valid email address.'],
        'Duplicate email' => ['email', 'existinguser@example.com', 'The email has already been taken.'],
        'Missing password' => ['password', null, 'The password field is required.'],
        'Short password' => ['password', '123', 'The password field must be at least 6 characters.'],
    ];
}

}
