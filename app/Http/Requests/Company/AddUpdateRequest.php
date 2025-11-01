<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddUpdateRequest extends FormRequest
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
        $companyId  = $this->company ?? null;

        return [
            'company_name' => [
                'required',
                'string',
                'max:255',
                $companyId
                    ? Rule::unique('companies', 'name')->ignore($companyId)
                    : 'unique:companies,name',
            ],
            'user_name' => $companyId ? 'string|max:255' : 'required|string|max:255',
            'user_email' => $companyId ? 'email' : 'required|email',
            'password' => [
                $companyId ? 'nullable' : 'required', // Password is required only when creating
                'min:8',
                'confirmed',
            ],
            'start_date' => ['required', 'date', 'before_or_equal:end_date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'storage_size_mb' => ['required', 'integer', 'min:0'],
        ];
    }
}
