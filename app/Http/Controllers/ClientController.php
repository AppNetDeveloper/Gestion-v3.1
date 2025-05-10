<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User; // <-- Importar el modelo User
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash; // <-- Para hashear la contraseña
use Illuminate\Support\Str; // <-- Para generar strings aleatorios
use Illuminate\Support\Facades\Mail; // <-- Para enviar email
use App\Mail\NewUserWelcomeMail; // <-- Crearás este Mailable después

use Illuminate\Support\Facades\DB; // Para transacciones

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'],
            ['name' => __('Clients'), 'url' => route('clients.index')],
        ];
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
            $data = Client::latest()->get();
            return DataTables::of($data)
                ->addIndexColumn()
                 ->addColumn('vat_rate_display', function($row) {
                    return $row->vat_rate !== null ? number_format($row->vat_rate, 2, ',', '.') . '%' : '-';
                })
                ->addColumn('action', function($row){
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
                    return $row->created_at ? $row->created_at->format('d/m/Y H:i') : '';
                })
                ->rawColumns(['action'])
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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email', // Email es ahora obligatorio y único en users
            'phone' => 'nullable|string|max:20',
            'vat_number' => 'nullable|string|max:20|unique:clients,vat_number',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
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

        DB::beginTransaction(); // Iniciar transacción

        try {
            $clientData = $request->only([
                 'name', 'email', 'phone', 'vat_number', 'vat_rate',
                 'address', 'city', 'postal_code', 'country', 'notes'
             ]);
            $clientData['vat_rate'] = $request->filled('vat_rate') ? $request->input('vat_rate') : null;

            // Lógica para crear o asociar usuario
            $user = User::where('email', $request->input('email'))->first();
            $newUserPassword = null;

            if (!$user) {
                // Crear nuevo usuario si no existe
                $newUserPassword = Str::random(10); // Generar contraseña aleatoria
                $user = User::create([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' => Hash::make($newUserPassword),
                    'email_verified_at' => now(), // Marcar como verificado directamente
                    // Puedes añadir otros campos por defecto para el usuario si es necesario
                ]);
                $user->assignRole('customer'); // Asignar rol 'customer'
                Log::info("New user created for client: {$user->email}");

                // (Opcional pero recomendado) Enviar email de bienvenida con la contraseña
                // Debes crear el Mailable: php artisan make:mail NewUserWelcomeMail
                // Mail::to($user->email)->send(new NewUserWelcomeMail($user, $newUserPassword));
                // Log::info("Welcome email sent to new user: {$user->email}");

            } else {
                // Si el usuario ya existe, simplemente nos aseguramos de que tenga el rol 'customer'
                // (o podrías decidir no hacer nada o mostrar un aviso)
                if (!$user->hasRole('customer')) {
                    $user->assignRole('customer');
                    Log::info("Existing user {$user->email} assigned 'customer' role.");
                }
            }

            // Asociar el user_id al cliente
            $clientData['user_id'] = $user->id;

            $client = Client::create($clientData);

            DB::commit(); // Confirmar transacción

            $successMessage = __('Client created successfully!');
            if ($newUserPassword) {
                // Podrías añadir la contraseña al mensaje de éxito para el admin (¡cuidado con la seguridad!)
                // O mejor, confiar en que el email se envió.
                // $successMessage .= ' ' . __('New user created with password: ') . $newUserPassword;
                // Por ahora, solo mensaje genérico.
                $successMessage .= ' ' . __('A new user account has been created for this client.');
            }

            return redirect()->route('clients.index')->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack(); // Revertir transacción en caso de error
            Log::error('Error creating client and/or user: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
            return redirect()->route('clients.index')->with('error', __('An error occurred while creating the client.'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function show(Client $client)
    {
        return response()->json($client);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Client  $client
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Client $client)
    {
        // Cargar el usuario asociado para mostrar su email si es necesario
        $client->load('user');
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
        // Obtener el usuario asociado al cliente, si existe
        $associatedUser = $client->user;
        $userIdToIgnore = $associatedUser ? $associatedUser->id : null;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // Email es ahora obligatorio. Si se cambia, debe ser único en users, ignorando el usuario actual si está asociado.
            'email' => 'required|email|max:255|unique:users,email,' . $userIdToIgnore,
            'phone' => 'nullable|string|max:20',
            'vat_number' => 'nullable|string|max:20|unique:clients,vat_number,'.$client->id,
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $clientData = $request->only([
                 'name', 'email', 'phone', 'vat_number', 'vat_rate',
                 'address', 'city', 'postal_code', 'country', 'notes'
             ]);
            $clientData['vat_rate'] = $request->filled('vat_rate') ? $request->input('vat_rate') : null;

            // Actualizar el email del usuario asociado si ha cambiado y el cliente tiene un usuario asociado
            if ($associatedUser && $associatedUser->email !== $request->input('email')) {
                $associatedUser->email = $request->input('email');
                // Si cambias el email de un usuario que implementa MustVerifyEmail,
                // Laravel puede requerir una nueva verificación.
                // Aquí lo marcamos como verificado ya que el admin lo está cambiando.
                $associatedUser->email_verified_at = now();
                $associatedUser->save();
            }
            // Si el cliente no tiene un usuario asociado pero se proporciona un email,
            // podríamos considerar crear uno aquí también, o manejarlo como una acción separada.
            // Por ahora, solo actualizamos el email del cliente.

            $client->update($clientData);

            DB::commit();
            return response()->json(['success' => __('Client updated successfully!')]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating client: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
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
        // Considerar qué hacer con el usuario asociado. ¿Eliminarlo también? ¿Desasociarlo?
        // Por ahora, solo eliminamos el cliente. La FK en 'clients' tiene onDelete('set null') para user_id.
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
