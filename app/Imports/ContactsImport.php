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
        $phone = $this->normalizePhone($row['phone'] ?? null);

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
     * Normaliza el formato del teléfono.
     */
    private function normalizePhone($phone)
    {
        if (!$phone) {
            return null;
        }

        // Eliminar todos los caracteres no numéricos.
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Si el número tiene un prefijo internacional con "00", lo convertimos.
        if (strpos($phone, '00') === 0) {
            $phone = substr($phone, 2);
        }

        return $phone;
    }

    /**
     * Validaciones para cada fila.
     *
     * Se valida que al menos uno de los campos: phone, address, email, web, telegram o name, tenga algún valor.
     * Para lograrlo, se hace que "name" sea requerido si todos los demás están vacíos.
     */
    public function rules(): array
    {
        return [
            '*.phone'    => ['nullable', 'regex:/^\d+$/'], // Solo números sin espacios ni letras, si se ingresa.
            '*.name'     => ['nullable', 'required_without_all:phone,address,email,web,telegram'],
            '*.address'  => ['nullable'],
            '*.email'    => ['nullable', 'email'],
            '*.web'      => ['nullable', 'url'],
            '*.telegram' => ['nullable'],
        ];
    }
}
