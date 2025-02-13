<?php

namespace App\Imports;

use App\Models\Contact;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Auth;

class ContactsImport implements ToModel, WithHeadingRow, SkipsEmptyRows, WithValidation
{
    public function model(array $row)
    {
        // Normalizar el teléfono (reemplaza + o 00 con el prefijo correcto)
        $phone = $this->normalizePhone($row['phone']);

        return Contact::updateOrCreate(
            ['phone' => $phone],
            [
                'user_id'  => Auth::id(),
                'name'     => $row['name'] ?? null,
                'address'  => $row['address'] ?? null,
                'email'    => $row['email'] ?? null,
                'web'      => $row['web'] ?? null,
                'telegram' => $row['telegram'] ?? null,
            ]
        );
    }

    /**
     * Normaliza el formato del teléfono
     */
    private function normalizePhone($phone)
    {
        if (!$phone) {
            return null;
        }
    
        // Eliminar todos los caracteres no numéricos
        $phone = preg_replace('/[^0-9]/', '', $phone);
    
        // Si el número tiene un prefijo internacional con "00", lo convertimos
        if (strpos($phone, '00') === 0) {
            $phone = substr($phone, 2);
        }
    
        return $phone;
    }
    

    /**
     * Validaciones para cada fila.
     */
    public function rules(): array
    {
        return [
            '*.phone' => ['required', 'regex:/^\d+$/'], // Solo números sin espacios ni letras
        ];
    }
}
