<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return User::whereHas('companies', function ($query) {
            $query->where('company_id', get_active_company());
        })->with('companyRoles')->get();
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Is Active',
            'Roles',
        ];
    }

    public function map($user): array
    {
        $roles = $user->companyRoles->pluck('role_name')->implode(', ');

        return [
            $user->name,
            $user->email,
            $user->is_active ? 'Active' : 'Inactive',
            $roles ?: 'None',
        ];
    }
}
