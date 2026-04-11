<?php

namespace App\Imports;

use App\Models\Tenant;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TenantsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row): Tenant
    {
        return new Tenant([
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? null,
            'address' => $row['address'] ?? null,
            'emergency_contact_name' => $row['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $row['emergency_contact_phone'] ?? null,
            'notes' => $row['notes'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ];
    }
}
