<?php

namespace App\Http\Requests\Folder;

use Illuminate\Foundation\Http\FormRequest;

class FolderRequest extends FormRequest
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
            'name' => ['required','max:150'],
            'parent_id' => 'nullable|exists:folders,id',
            'permissions' => ['nullable','array'],
            'item_index' => ['nullable','numeric','digits_between:1,10'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Folder Name',
            'parent_id' => 'Parent Folder',
            'item_index' => 'Item Index',
        ];
    }
}
