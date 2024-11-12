<?php

namespace App\Http\Controllers\RoleAndPermission;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest\StoreUserRequest;
use App\Http\Requests\UserRequest\UpdateUserRequest;

class UserController extends Controller
{
    protected $userService;

    /**
     * Create a new controller instance.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Create a new user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createUser(StoreUserRequest $request)
    {

            $user = $this->userService->createUser($request->validated());

            $this->userService->assignRole($user->id, $request->role);

            return response()->json(['message' => 'User created successfully.', 'user' => $user], 201);

    }

    /**
     * Get all users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsers()
    {
        try {
            $users = $this->userService->getUsers();

            return response()->json(['users' => $users], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a specific user by ID.
     *
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUser($userId)
    {
        try {
            $user = $this->userService->getUser($userId);

            return response()->json(['user' => $user], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // /**
    //  * Update a user's details.
    //  *
    //  * @param Request $request
    //  * @param int $userId
    //  * @return \Illuminate\Http\JsonResponse
    //  */
    // public function updateUser(Request $request, $userId)
    // {
    //     try {
    //         // Validate request
    //         $request->validate([
    //             'name' => 'nullable|string|max:255',
    //             'email' => 'nullable|email|unique:users,email,' . $userId,
    //             'role' => 'nullable|exists:roles,name',
    //         ]);

    //         // Update user
    //         $user = $this->userService->updateUser($userId, $request->all());

    //         if ($request->role) {
    //             // Update role if provided
    //             $this->userService->assignRole($user->id, $request->role);
    //         }

    //         return response()->json(['message' => 'User updated successfully.', 'user' => $user], 200);
    //     } catch (Exception $e) {
    //         return response()->json(['message' => $e->getMessage()], 500);
    //     }
    // }
/**
 * Update a user's details.
 *
 * @param UpdateUserRequest $request
 * @param int $userId
 * @return \Illuminate\Http\JsonResponse
 */
public function updateUser(UpdateUserRequest $request, $userId)
{
    try {
      
        $user = $this->userService->updateUser($userId, $request->validated());

        if ($request->role) {
            $this->userService->assignRole($user->id, $request->role);
        }

        return response()->json(['message' => 'User updated successfully.', 'user' => $user], 200);
    } catch (Exception $e) {
        return response()->json(['message' => $e->getMessage()], 500);
    }
}
    /**
     *  delete a user.
     *
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteUser($userId)
    {
        try {
            $user = $this->userService->deleteUser($userId);

            return response()->json(['message' => 'User deleted successfully.', 'user' => $user], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }



}
