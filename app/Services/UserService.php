<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Exception;

class UserService
{
    /**
     * Create a new user.
     *
     * @param array $data
     * @return User
     * @throws Exception
     */
    public function createUser(array $data)
    {
        try {
            // Create user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
            ]);

            return $user;
        } catch (Exception $e) {
            Log::error('Failed to create user: ' . $e->getMessage());
            throw new Exception('Failed to create user.');
        }
    }

    /**
     * Get all users.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws Exception
     */
    public function getUsers()
    {
        try {
            // Retrieve all users, including soft deleted ones
            return User::all();
        } catch (Exception $e) {
            Log::error('Failed to retrieve users: ' . $e->getMessage());
            throw new Exception('Failed to retrieve users.');
        }
    }

    /**
     * Get a specific user by ID.
     *
     * @param int $userId
     * @return User
     * @throws Exception
     */
    public function getUser($userId)
    {
        try {
            // Retrieve user by ID, including soft deleted ones
            return User::findOrFail($userId);
        } catch (Exception $e) {
            Log::error('Failed to retrieve user: ' . $e->getMessage());
            throw new Exception('Failed to retrieve user.');
        }
    }

    /**
     * Update a user's details.
     *
     * @param int $userId
     * @param array $data
     * @return User
     * @throws Exception
     */
    public function updateUser(int $userId, array $data)
    {
        try {
            // Find user by ID
            $user = User::findOrFail($userId);

            // Update user data
            $user->update($data);

            return $user;
        } catch (Exception $e) {
            Log::error('Failed to update user: ' . $e->getMessage());
            throw new Exception('Failed to update user.');
        }
    }

    /**
     * Soft delete a user.
     *
     * @param int $userId
     * @return User
     * @throws Exception
     */
    public function deleteUser(int $userId)
    {
        try {
            // Find user by ID
            $user = User::findOrFail($userId);

            // Soft delete user
            $user->delete();

            return $user;
        } catch (Exception $e) {
            Log::error('Failed to delete user: ' . $e->getMessage());
            throw new Exception('Failed to delete user.');
        }
    }

    /**
     * Restore a soft deleted user.
     *
     * @param int $userId
     * @return User
     * @throws Exception
     */
    public function restoreUser(int $userId)
    {
        try {
            // Find user by ID, including soft deleted ones
            $user = User::withTrashed()->findOrFail($userId);

            // Restore user
            $user->restore();

            return $user;
        } catch (Exception $e) {
            Log::error('Failed to restore user: ' . $e->getMessage());
            throw new Exception('Failed to restore user.');
        }
    }

    /**
     * Assign a role to a user.
     *
     * @param int $userId
     * @param string $role
     * @throws Exception
     */
    public function assignRole(int $userId, string $role)
    {
        try {
            $user = User::findOrFail($userId);

            // Assuming the roles are stored in a roles table and related to the user
            $user->assignRole($role);
        } catch (Exception $e) {
            Log::error('Failed to assign role to user: ' . $e->getMessage());
            throw new Exception('Failed to assign role.');
        }
    }
}
