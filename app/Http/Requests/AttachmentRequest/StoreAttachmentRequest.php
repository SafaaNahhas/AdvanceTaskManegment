<?php

namespace App\Http\Requests\AttachmentRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
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
            'attachment' => 'required|file|max:2048|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx',

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
            'attachment.required' => 'An attachment is required.',
            'attachment.file' => 'The attachment must be a file.',
            'attachment.max' => 'The attachment may not be larger than 2MB.',
            'attachment.mimes' => 'The attachment must be a file of type: jpg, jpeg, png, gif, webp, pdf, doc, docx.',
        ];
    }
}
