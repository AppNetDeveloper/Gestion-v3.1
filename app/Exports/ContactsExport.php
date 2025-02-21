<?php

namespace App\Exports;

use App\Models\Contact;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ContactsExport implements FromCollection, WithHeadings
{
    /**
     * Retorna los datos de la tabla contacts.
     */
    public function collection()
    {
        return Contact::select('name', 'phone', 'address', 'email', 'web', 'telegram')->get();
    }

    /**
     * Definir los encabezados de la hoja de Excel.
     */
    public function headings(): array
    {
        return ['Name', 'Phone', 'Address', 'Email', 'Web', 'Telegram'];
    }
}
