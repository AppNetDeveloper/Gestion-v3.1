<?php

namespace App\Http\Controllers;

use App\Models\Client; // Importa tu modelo Client
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables; // Si usas Yajra DataTables
use Illuminate\Support\Facades\Log; // Para loguear errores

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Prepara datos básicos para la vista principal
        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'], // Ajusta URL si es necesario
            ['name' => __('Clients'), 'url' => route('clients.index')],
        ];
        // La tabla se carga vía AJAX, solo pasamos los breadcrumbs
        return view('clients.index', compact('breadcrumbItems'));
    }

    /**
     * Fetch data for DataTables.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(Request $request)
    {
        if ($request->ajax()) {
            $data = Client::latest()->get(); // Obtiene los clientes más recientes primero
            return DataTables::of($data)
                ->addIndexColumn()
                 ->addColumn('vat_rate_display', function($row) { // Columna virtual para mostrar formateado
                    return $row->vat_rate !== null ? number_format($row->vat_rate, 2, ',', '.') . '%' : '-';
                })
                ->addColumn('action', function($row){
                    // Botones de acción (Editar, Eliminar)
                    // *** AÑADIDO data-vat_rate ***
                    $editBtn = '<button class="editClient text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors duration-150 p-1"
                                    data-id="'.$row->id.'"
                                    data-name="'.htmlspecialchars($row->name, ENT_QUOTES).'"
                                    data-email="'.htmlspecialchars($row->email ?? '', ENT_QUOTES).'"
                                    data-phone="'.htmlspecialchars($row->phone ?? '', ENT_QUOTES).'"
                                    data-vat_number="'.htmlspecialchars($row->vat_number ?? '', ENT_QUOTES).'"
                                    data-vat_rate="'.$row->vat_rate.'"
                                    data-address="'.htmlspecialchars($row->address ?? '', ENT_QUOTES).'"
                                    data-city="'.htmlspecialchars($row->city ?? '', ENT_QUOTES).'"
                                    data-postal_code="'.htmlspecialchars($row->postal_code ?? '', ENT_QUOTES).'"
                                    data-country="'.htmlspecialchars($row->country ?? '', ENT_QUOTES).'"
                                    data-notes="'.htmlspecialchars($row->notes ?? '', ENT_QUOTES).'"
                                    title="'.__('Edit Client').'">
                                    <iconify-icon icon="heroicons:pencil-square" style="font-size: 1.25rem;"></iconify-icon>
                                </button>';
                    $deleteBtn = '<button class="deleteClient text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-150 p-1"
                                    data-id="'.$row->id.'" title="'.__('Delete Client').'">
                                    <iconify-icon icon="heroicons:trash" style="font-size: 1.25rem;"></iconify-icon>
                                 </button>';
                    return '<div class="flex items-center justify-center space-x-2">'.$editBtn . $deleteBtn.'</div>';
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? $row->created_at->format('d/m/Y H:i') : ''; // Formatear fecha
                })
                ->rawColumns(['action']) // Permite HTML en la columna 'action'
                ->make(true);
        }
        return abort(403, 'Unauthorized action.');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create()
    {
        // El formulario de creación está en la vista index.
        return redirect()->route('clients.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // *** AÑADIDA VALIDACIÓN PARA vat_rate ***
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'vat_number' => 'nullable|string|max:20|unique:clients,vat_number', // NIF/CIF
            'vat_rate' => 'nullable|numeric|min:0|max:100', // Validación para la tasa de IVA
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('clients.index')
                        ->withErrors($validator)
                        ->withInput()
                        ->with('error', __('Failed to create client. Please check the errors.'));
        }

        try {
            // *** AÑADIDO vat_rate a los datos a crear ***
            // Usar only() para asegurar que solo pasamos los campos permitidos por $fillable
             $clientData = $request->only([
                 'name', 'email', 'phone', 'vat_number', 'vat_rate',
                 'address', 'city', 'postal_code', 'country', 'notes'
             ]);
             // Convertir vat_rate a null si está vacío para evitar problemas con la BD si no es nullable
             $clientData['vat_rate'] = $request->filled('vat_rate') ? $request->input('vat_rate') : null;

            Client::create($clientData);
            return redirect()->route('clients.index')->with('success', __('Client created successfully!'));
        } catch (\Exception $e) {
            Log::error('Error creating client: '.$e->getMessage());
            return redirect()->route('clients.index')->with('error', __('An error occurred while creating the client.'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Client  $client
     * @return \Illuminate\Http\Response // O JsonResponse, o View
     */
    public function show(Client $client)
    {
        // Aquí podrías cargar una vista de detalle del cliente con sus proyectos, presupuestos, etc.
        // Ejemplo: return view('clients.show', compact('client'));
        return response()->json($client); // Para pruebas o API
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Client  $client
     * @return \Illuminate\Http\JsonResponse // O View, o RedirectResponse
     */
    public function edit(Client $client)
    {
        // Probablemente manejado por modal, así que devolvemos JSON
        // El vat_rate ya se carga automáticamente con el modelo $client
        return response()->json($client);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Client  $client
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Client $client)
    {
        // *** AÑADIDA VALIDACIÓN PARA vat_rate ***
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:clients,email,'.$client->id,
            'phone' => 'nullable|string|max:20',
            'vat_number' => 'nullable|string|max:20|unique:clients,vat_number,'.$client->id,
            'vat_rate' => 'nullable|numeric|min:0|max:100', // Validación para la tasa de IVA
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            // Devolver errores JSON para el modal de SweetAlert
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
             // *** AÑADIDO vat_rate a los datos a actualizar ***
             $clientData = $request->only([
                 'name', 'email', 'phone', 'vat_number', 'vat_rate',
                 'address', 'city', 'postal_code', 'country', 'notes'
             ]);
             $clientData['vat_rate'] = $request->filled('vat_rate') ? $request->input('vat_rate') : null;

            $client->update($clientData);
            return response()->json(['success' => __('Client updated successfully!')]);
        } catch (\Exception $e) {
            Log::error('Error updating client: '.$e->getMessage());
            return response()->json(['error' => __('An error occurred while updating the client.')], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Client  $client
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Client $client)
    {
        try {
            $client->delete();
            return response()->json(['success' => __('Client deleted successfully!')]);
        } catch (\Exception $e) {
            Log::error('Error deleting client: '.$e->getMessage());
             if (str_contains($e->getMessage(), 'foreign key constraint fails')) {
                 return response()->json(['error' => __('Cannot delete client because they have associated records (quotes, projects, invoices).')], 409);
             }
            return response()->json(['error' => __('An error occurred while deleting the client.')], 500);
        }
    }
}
