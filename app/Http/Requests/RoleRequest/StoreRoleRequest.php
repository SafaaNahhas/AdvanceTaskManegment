<?php

namespace App\Http\Requests\RoleRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
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
            'name' => 'required|unique:roles,name' ,
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',        ];
    }
 /**
     * Custom messages for validation errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'اسم الدور مطلوب',
            'name.unique' => 'اسم الدور يجب أن يكون فريدًا',
            'permissions.array' => 'الصلاحيات يجب أن تكون في هيئة قائمة',
            'permissions.*.exists' => 'الصلاحية المحددة غير موجودة',
        ];
    }
}
