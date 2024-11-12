<?php

namespace App\Http\Requests\TaskRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
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
            'title'         => 'required|string|max:255|unique:tasks,title|regex:/^[a-zA-Z0-9\s]+$/',
            'description'   => 'nullable|string|max:1000',
            'type'          => 'required|in:Bug,Feature,Improvement',
            'priority'      => 'required|in:Low,Medium,High',
            'due_date'      => 'required|date|after_or_equal:today',
            'assigned_to'   => 'nullable|exists:users,id',
            'dependencies'  => 'nullable|array',
            'dependencies.*'=> 'exists:tasks,id',
        ];
    }
    /**
     * Get custom messages for validation errors.
     *
     * @return array
     */
    public function messages()
{
    return [
        'title.required' => 'The title field is required.',
        'title.unique' => 'The title must be unique.',
        'title.regex' => 'The title contains invalid characters.',
        'type.required' => 'The task type is required.',
        'type.in' => 'The selected type is invalid. It must be Bug, Feature, or Improvement.',
        'priority.required' => 'The task priority is required.',
        'priority.in' => 'The selected priority is invalid. It must be Low, Medium, or High.',
        'due_date.date' => 'The due date must be a valid date.',
        'due_date.after_or_equal' => 'The due date must be today or in the future.',
        'due_date.required' => 'The due date is required.',
        'assigned_to.exists' => 'The selected user does not exist.',
        'dependencies.array' => 'The dependencies must be an array.',
        'dependencies.*.exists' => 'One of the selected dependencies does not exist.',
    ];
}

}
