<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRoleRequest extends FormRequest
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
        $companyId = get_active_company();
        $id = $this->companyrole ?? null;
        return [
            'role_name' => [
                'required',
                Rule::unique('company_roles')->where('company_id', $companyId)->ignore($id) ,
                'string',
                'max:50'
            ]
        ];
    }
}
