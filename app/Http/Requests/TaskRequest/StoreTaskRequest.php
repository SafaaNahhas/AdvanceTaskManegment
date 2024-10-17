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
            'title'         => 'required|string|max:255|unique:tasks,title|regex:/^[a-zA-Z0-9\s\-]+$/',
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
            'title.required' => 'عنوان المهمة مطلوب.',
            'title.unique' => 'عنوان المهمة يجب أن يكون فريدًا.',
            'title.regex' => 'عنوان المهمة يحتوي على أحرف غير مسموح بها.',
            'type.required' => 'نوع المهمة مطلوب.',
            'type.in' => 'نوع المهمة غير صالح. يجب أن يكون Bug، Feature، أو Improvement.',
            'priority.required' => 'أولوية المهمة مطلوبة.',
            'priority.in' => 'أولوية المهمة غير صالحة. يجب أن تكون Low، Medium، أو High.',
            'due_date.date' => 'تاريخ الاستحقاق يجب أن يكون تاريخًا صالحًا.',
            'due_date.after_or_equal' => 'تاريخ الاستحقاق يجب أن يكون في المستقبل أو اليوم.',
            'due_date.required' => 'تاريخ الاستحقاق مطلوب',
            'assigned_to.exists' => 'المستخدم المحدد غير موجود.',
            'dependencies.array' => 'الاعتماديات يجب أن تكون مصفوفة.',
            'dependencies.*.exists' => 'إحدى الاعتماديات المحددة غير موجودة.',
        ];
    }
}
