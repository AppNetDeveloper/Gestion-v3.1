<?php

namespace App\Http\Controllers;

use App\Models\Scraping;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ScrapingController extends Controller
{
    /**
     * Constructor del controlador.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Configurar los elementos del breadcrumb
        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => route('dashboard.unified')],
            ['name' => __('Scraping Management')]
        ];

        return view('scrapings.index', compact('breadcrumbItems'));
    }

    /**
     * Obtener datos para la tabla de tareas de scraping (para DataTables).
     */
    public function data()
    {
        $scrapings = Scraping::where('user_id', Auth::id())
            ->withCount('contacts')
            ->orderBy('id', 'desc');

        return DataTables::of($scrapings)
            ->addColumn('action', function ($scraping) {
                return ''; // La acción se maneja en el frontend con JavaScript
            })
            ->editColumn('created_at', function ($scraping) {
                return $scraping->created_at->format('d/m/Y H:i');
            })
            ->make(true);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'keywords' => 'required|string|max:1000',
            'linkedin_username' => 'nullable|string|max:255',
            'linkedin_password' => 'nullable|string|max:255',
        ]);

        try {
            // Generar un tasker_id único
            $taskerId = 'SCRAPER_TASK_' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8)) . '_' . time();
            
            $scraping = Scraping::create([
                'user_id' => Auth::id(),
                'keywords' => $request->keywords,
                'status' => Scraping::STATUS_PENDING,
                'linkedin_username' => $request->linkedin_username,
                'linkedin_password' => $request->linkedin_password,
                'tasker_id' => $taskerId
            ]);

            return redirect()->route('scrapings.index')
                ->with('success', __('Scraping task created successfully.'));
        } catch (\Exception $e) {
            Log::error('Error creating scraping task: ' . $e->getMessage());
            return redirect()->route('scrapings.index')
                ->with('error', __('An error occurred while creating the scraping task.'));
        }
    }

    /**
     * Display the contacts associated with a scraping task.
     */
    public function showContacts(string $id)
    {
        $scraping = Scraping::where('user_id', Auth::id())->findOrFail($id);
        $contacts = $scraping->contacts;

        // Configurar los elementos del breadcrumb
        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => route('dashboard.unified')],
            ['name' => __('Scraping Management'), 'url' => route('scrapings.index')],
            ['name' => __('Contacts for Scraping Task #') . $id]
        ];

        return view('scrapings.contacts', compact('scraping', 'contacts', 'breadcrumbItems'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $scraping = Scraping::where('user_id', Auth::id())->findOrFail($id);
            
            // Eliminar la relación con los contactos (no elimina los contactos)
            $scraping->contacts()->detach();
            
            // Eliminar la tarea de scraping
            $scraping->delete();

            return response()->json([
                'success' => __('Scraping task deleted successfully.')
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting scraping task: ' . $e->getMessage());
            return response()->json([
                'error' => __('An error occurred while deleting the scraping task.')
            ], 500);
        }
    }
}
