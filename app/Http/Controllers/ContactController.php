<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use App\Imports\ContactsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Muestra el listado de contactos (solo para el usuario autenticado).
     */
    public function index()
    {
        // Solo se obtienen los contactos cuyo user_id coincida con el ID del usuario logueado
        $contacts = Contact::where('user_id', Auth::id())->get();
        return view('contacts.index', compact('contacts'));
    }

    /**
     * Muestra el formulario para crear un nuevo contacto.
     */
    public function create()
    {
        return view('contacts.create');
    }

    /**
     * Almacena un nuevo contacto.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'required|string|max:50',
            'address' => 'nullable|string|max:255',
            'email'   => 'nullable|email|max:255',
            'web'     => 'nullable|url|max:255',
            'telegram'=> 'nullable|string|max:255',
        ]);

        // Asignamos el user_id del usuario autenticado
        $validated['user_id'] = Auth::id();

        Contact::create($validated);

        return redirect()->route('contacts.index')
                         ->with('success', 'Contact created successfully.');
    }

    /**
     * Muestra el formulario para editar un contacto.
     */
    public function edit($id)
    {
        // Se busca el contacto SOLO entre los que pertenecen al usuario logueado
        $contact = Contact::where('user_id', Auth::id())->findOrFail($id);
        return view('contacts.edit', compact('contact'));
    }

    /**
     * Actualiza el contacto.
     */
    public function update(Request $request, $id)
    {
        $contact = Contact::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'required|string|max:50',
            'address' => 'nullable|string|max:255',
            'email'   => 'nullable|email|max:255',
            'web'     => 'nullable|url|max:255',
            'telegram'=> 'nullable|string|max:255',
        ]);

        $contact->update($validated);

        return redirect()->route('contacts.index')
                         ->with('success', 'Contact updated successfully.');
    }

    /**
     * Elimina el contacto.
     */
    public function destroy($id)
    {
        $contact = Contact::where('user_id', Auth::id())->findOrFail($id);
        $contact->delete();

        return redirect()->route('contacts.index')
                         ->with('success', 'Contact deleted successfully.');
    }

    /**
     * Importa contactos desde un archivo Excel.
     */
    public function import(Request $request)
    {
        // Validar que el archivo es obligatorio y de tipo Excel
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            // Importar los contactos desde el archivo Excel
            Excel::import(new ContactsImport, $request->file('file')->store('temp'));

            return redirect()->route('contacts.index')->with('success', 'Contacts imported successfully.');
        } catch (\Exception $e) {
            Log::error('Error importing contacts: ' . $e->getMessage());
            return redirect()->route('contacts.index')->with('error', 'Error importing contacts: ' . $e->getMessage());
        }
    }

}
