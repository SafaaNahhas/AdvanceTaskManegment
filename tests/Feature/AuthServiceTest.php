<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AuthServiceTest extends TestCase
{
    protected $authService;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = app(AuthService::class);
    }

    /** @test */
    public function test_login_with_valid_credentials()
    {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $credentials = ['email' => 'test@example.com', 'password' => 'password123'];
    $response = $this->authService->login($credentials);

    if ($response instanceof \Illuminate\Http\JsonResponse) {
        $data = $response->getData(true);
    } else {
        $data = $response;
    }

    $this->assertArrayHasKey('token', $data);
    $this->assertArrayHasKey('csrf_token', $data);
    }

    /** @test */
    public function test_login_with_invalid_credentials()
    {
        $credentials = ['email' => 'wrong@example.com', 'password' => 'wrongpassword'];
        $response = $this->authService->login($credentials);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $this->assertEquals(401, $response->status());
            $this->assertEquals('Unauthorized', $response->getData(true)['error']);
        } else {
            $this->assertEquals(401, $response['status']);
            $this->assertEquals('Unauthorized', $response['error']);
        }
    }

        /** @test */
        public function test_register_user_successfully()
        {
        $data = [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ];

        $response = $this->authService->register($data);

        $this->assertEquals(201, $response['status']);
        $this->assertArrayHasKey('token', $response);
        $this->assertArrayHasKey('csrf_token', $response);
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }


        /** @test */
    public function test_logout_user_successfully()
    {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $this->withHeaders([
            'Authorization' => "Bearer $token",
        ]);

        $response = $this->authService->logout();

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $this->assertEquals(200, $response->status());
            $this->assertEquals('Successfully logged out', $response->getData(true)['message']);
        } else {
            $this->assertEquals(200, $response['status']);
            $this->assertEquals('Successfully logged out', $response['message']);
        }
    }

    /** @test */
    public function test_refresh_token_successfully()
    {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $this->withHeaders([
            'Authorization' => "Bearer $token",
        ]);

        $response = $this->authService->refresh();

        Log::info('Refresh token response:', ['response' => $response]);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $this->assertArrayHasKey('access_token', $response->getData(true));
        } else {
            $this->assertArrayHasKey('access_token', $response);
        }
}




}
