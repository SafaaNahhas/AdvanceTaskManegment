<?php

namespace App\Http\Requests\TaskRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskStatusRequest extends FormRequest
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
            'status' => 'required|in:Open,In Progress,Completed,Blocked',
        ];
    }
    /**
     * Get custom messages for validation errors.
     *
     * @return array
     */
    public function messages()
    {
        return[
            'status.required' => 'حالة المهمة مطلوبة.',
            'status.in' => 'حالة المهمة غير صالحة. يجب أن يكون In Progress Completed أو Blocked.',
        ];
    }
}
