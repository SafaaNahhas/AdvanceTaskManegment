<?php

namespace App\Http\Requests\CommentRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
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
            'comment' => 'required|string|max:1000',
        ];
    }
     /**
     * Customize the validation error messages.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'comment.required' => 'A comment is required.',
            'comment.string' => 'The comment must be a string.',
            'comment.max' => 'The comment may not be larger than 1000 characters.',
        ];
    }
}
