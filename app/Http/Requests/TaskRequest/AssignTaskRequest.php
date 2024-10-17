<?php

namespace App\Http\Requests\TaskRequest;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class AssignTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => 'required|exists:users,id',
        ];
    }
    
      /**
     * Add custom validation logic for preventing tasks from being assigned to admins.
     */
    protected function prepareForValidation()
    {
        // Retrieve the user to whom the task is being assigned
        $assignedUser = User::find($this->assigned_to);

        // If the user exists and has an "admin" role, throw a validation error
        if ($assignedUser && $assignedUser->hasRole('admin')) {
            // Throw a validation exception with a custom error message
            throw ValidationException::withMessages([
                'assigned_to' => 'Tasks cannot be assigned to admin users.',
            ]);
        }
    }
     /**
     * Get custom messages for validation errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'assigned_to.required' => 'The assigned user is required.',
            'assigned_to.exists'   => 'The selected user does not exist.',
            'assigned_to.invalid'  => 'Tasks cannot be assigned to admin users.', // Custom error message

        ];
    }
}
