<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        $user = $this->user ?? null;
        $activeCompany = get_active_company();
        
        $rules = [
            'user_name' => 'required|string|max:100',
            'user_email' => [
                'required',
                'email',
                $user
                    ? Rule::unique('users', 'email')
                        ->ignore($user)
                        ->where(function ($query) use ($activeCompany) {
                            $query->whereIn('id', function ($subQuery) use ($activeCompany) {
                                $subQuery->select('user_id')
                                    ->from('company_users')
                                    ->where('company_id', $activeCompany);
                            });
                        })
                    : Rule::unique('users', 'email')
                        ->where(function ($query) use ($activeCompany) {
                            $query->whereIn('id', function ($subQuery) use ($activeCompany) {
                                $subQuery->select('user_id')
                                    ->from('company_users')
                                    ->where('company_id', $activeCompany);
                            });
                        }),
            ],
            'role' => [
                'nullable',
                Rule::exists('company_roles', 'id')
                    ->where(function ($query) use ($activeCompany) {
                        $query->where('role_name', '!=', 'Super Admin')
                            ->where('company_id', $activeCompany);
                    }),
            ],
        ];

        // Add password validation rules only for edit if password is provided
        if ($this->user && $this->filled('password')) {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        return $rules;
    }
}
