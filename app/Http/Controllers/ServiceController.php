<?php

namespace App\Http\Controllers;

use App\Models\Service; // Importa tu modelo Service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; // Para validación
use Yajra\DataTables\Facades\DataTables; // Si usas Yajra DataTables

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Los datos para la tabla se cargarán vía AJAX a través del método data()
        // Aquí solo preparamos los datos para la vista principal, como el breadcrumb
        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'], // Ajusta la URL del dashboard si es diferente
            ['name' => __('Services'), 'url' => route('services.index')],
        ];
        return view('services.index', compact('breadcrumbItems'));
    }

    /**
     * Fetch data for DataTables.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(Request $request)
    {
        if ($request->ajax()) {
            $data = Service::latest()->get(); // Obtiene los servicios, los más recientes primero
            return DataTables::of($data)
                ->addIndexColumn() // Añade una columna DT_RowIndex para numeración
                ->addColumn('action', function($row){
                    // Botones de acción (Editar, Eliminar)
                    // Adaptaremos esto para que coincida con el estilo de tu vista de campañas
                    $editBtn = '<button class="editService text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors duration-150 p-1"
                                    data-id="'.$row->id.'"
                                    data-name="'.htmlspecialchars($row->name, ENT_QUOTES).'"
                                    data-description="'.htmlspecialchars($row->description, ENT_QUOTES).'"
                                    data-default_price="'.$row->default_price.'"
                                    data-unit="'.htmlspecialchars($row->unit, ENT_QUOTES).'"
                                    title="'.__('Edit Service').'">
                                    <iconify-icon icon="heroicons:pencil-square" style="font-size: 1.25rem;"></iconify-icon>
                                </button>';
                    $deleteBtn = '<button class="deleteService text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-150 p-1"
                                    data-id="'.$row->id.'" title="'.__('Delete Service').'">
                                    <iconify-icon icon="heroicons:trash" style="font-size: 1.25rem;"></iconify-icon>
                                 </button>';
                    return '<div class="flex items-center justify-center space-x-2">'.$editBtn . $deleteBtn.'</div>';
                })
                ->editColumn('default_price', function($row) {
                    return number_format($row->default_price, 2, ',', '.') . ' €'; // Formatear precio
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
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Normalmente, el formulario de creación está en la vista index.
        // Si tienes una vista separada, la retornarías aquí.
        return redirect()->route('services.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:services,name',
            'description' => 'nullable|string',
            'default_price' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return redirect()->route('services.index')
                        ->withErrors($validator)
                        ->withInput()
                        ->with('error', __('Failed to create service. Please check the errors.'));
        }

        try {
            Service::create($request->only(['name', 'description', 'default_price', 'unit']));
            return redirect()->route('services.index')->with('success', __('Service created successfully!'));
        } catch (\Exception $e) {
            // Log::error('Error creating service: '.$e->getMessage()); // Es bueno loguear el error
            return redirect()->route('services.index')->with('error', __('An error occurred while creating the service.'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function show(Service $service)
    {
        // Podrías retornar una vista de detalle si es necesario, o usarlo para API.
        // return view('services.show', compact('service'));
        return response()->json($service); // Ejemplo si se usa para una API o modal
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function edit(Service $service)
    {
        // El formulario de edición se manejará probablemente con un modal en la vista index,
        // similar a tu ejemplo de campañas.
        // Si tienes una vista de edición separada:
        // return view('services.edit', compact('service'));
        return response()->json($service); // Para cargar datos en un modal de edición
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Service $service)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:services,name,'.$service->id,
            'description' => 'nullable|string',
            'default_price' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $service->update($request->only(['name', 'description', 'default_price', 'unit']));
            return response()->json(['success' => __('Service updated successfully!')]);
        } catch (\Exception $e) {
            // Log::error('Error updating service: '.$e->getMessage());
            return response()->json(['error' => __('An error occurred while updating the service.')], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Service $service)
    {
        try {
            $service->delete();
            return response()->json(['success' => __('Service deleted successfully!')]);
        } catch (\Exception $e) {
            // Log::error('Error deleting service: '.$e->getMessage());
            // Podrías verificar si el servicio tiene relaciones que impidan su borrado
            // y devolver un mensaje más específico.
            return response()->json(['error' => __('An error occurred while deleting the service.')], 500);
        }
    }
}
