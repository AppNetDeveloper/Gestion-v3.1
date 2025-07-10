<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\KnowledgeBaseFile;
use App\Models\KnowledgeBase;
use App\Models\User;
use App\Jobs\ProcessPdfForRagJob; // Importamos el Job para el procesamiento
use App\Helpers\StorageHelper; // Importamos nuestro helper personalizado
use Illuminate\Support\Facades\DB; // Para consultas avanzadas

class KnowledgeBaseController extends Controller
{
    /**
     * Muestra la vista principal de la base de conocimiento con las tablas.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Obtenemos estadísticas para el dashboard
        $stats = $this->getKnowledgeBaseStats();
        
        return view('knowledge_base.index', compact('stats'));
    }
    
    /**
     * Obtiene estadísticas para el dashboard de la IA Memory
     *
     * @return array
     */
    private function getKnowledgeBaseStats()
    {
        $userId = Auth::id();
        $userCanViewCompany = Auth::user()->can('viewCompanyKnowledge', KnowledgeBaseFile::class);
        
        // Consulta base para documentos
        $query = KnowledgeBaseFile::query();
        
        // Filtrar según permisos
        if (!$userCanViewCompany) {
            $query->where('user_id', $userId);
        } else {
            $query->where(function($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereNull('user_id');
            });
        }
        
        // Total de documentos
        $totalDocuments = $query->count();
        
        // Documentos procesados (tienen al menos un chunk)
        $processedDocuments = $query->whereHas('knowledgeChunks')->count();
        
        // Documentos en procesamiento (no tienen chunks pero tienen job pendiente)
        $processingDocuments = $query->whereDoesntHave('knowledgeChunks')->count();
        
        // Total de chunks
        $totalChunks = KnowledgeBase::when(!$userCanViewCompany, function($q) use ($userId) {
            return $q->whereHas('file', function($query) use ($userId) {
                $query->where('user_id', $userId);
            });
        })->when($userCanViewCompany, function($q) use ($userId) {
            return $q->whereHas('file', function($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhereNull('user_id');
            });
        })->count();
        
        return [
            'total_documents' => $totalDocuments,
            'processed_documents' => $processedDocuments,
            'processing_documents' => $processingDocuments,
            'total_chunks' => $totalChunks,
        ];
    }
    
    /**
     * Devuelve las estadísticas de la base de conocimiento en formato JSON
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $stats = $this->getKnowledgeBaseStats();
        
        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }
    
    /**
     * Muestra el formulario para subir archivos PDF a la base de conocimiento.
     * Este método es necesario para la ruta knowledge_base.upload.
     *
     * @return \Illuminate\View\View
     */
    public function showUploadForm()
    {
        return view('knowledge_base.upload');
    }

    /**
     * Proporciona los datos para la DataTable de los PDFs del usuario.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userData(Request $request)
    {
        $userId = Auth::id();
        $pdfs = KnowledgeBaseFile::where('user_id', $userId)
            ->withCount('knowledgeChunks')
            ->orderByDesc('created_at');
            
        return datatables()->of($pdfs)
            ->addColumn('file_name', function($row) {
                return e($row->original_name);
            })
            ->addColumn('type', function($row) {
                return 'user';
            })
            ->addColumn('processed', function($row) {
                return $row->knowledge_chunks_count > 0;
            })
            ->addColumn('processing', function($row) {
                return $row->knowledge_chunks_count == 0;
            })
            ->addColumn('status', function($row) {
                return $row->knowledge_chunks_count > 0 ? 'processed' : 'processing';
            })
            ->addColumn('action', function($row) {
                $downloadUrl = route('knowledge_base.download', $row->id);
                $deleteUrl = route('knowledge_base.delete', $row->id);
                
                return '<div class="flex justify-center space-x-2">
                    <a href="' . $downloadUrl . '" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-xs p-2 text-center inline-flex items-center dark:bg-blue-700 dark:hover:bg-blue-800 dark:focus:ring-blue-900" title="' . __('Descargar') . '">
                        <iconify-icon icon="heroicons:arrow-down-tray" class="w-4 h-4"></iconify-icon>
                    </a>
                    <button onclick="deletePdf(this)" data-url="' . $deleteUrl . '" class="text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-xs p-2 text-center inline-flex items-center dark:bg-red-700 dark:hover:bg-red-800 dark:focus:ring-red-900" title="' . __('Eliminar') . '">
                        <iconify-icon icon="heroicons:trash" class="w-4 h-4"></iconify-icon>
                    </button>
                </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Proporciona los datos para la DataTable de los PDFs de la empresa.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function companyData(Request $request)
    {
        // Es mejor usar Policies o Gates para la gestión de permisos
        if (!Auth::user()->can('viewCompanyKnowledge', KnowledgeBaseFile::class)) {
            return datatables()->of([])->make(true);
        }
        
        $pdfs = KnowledgeBaseFile::whereNull('user_id')
            ->withCount('knowledgeChunks')
            ->orderByDesc('created_at');

        return datatables()->of($pdfs)
            ->addColumn('file_name', function($row) {
                return e($row->original_name);
            })
            ->addColumn('type', function($row) {
                return 'company';
            })
            ->addColumn('processed', function($row) {
                return $row->knowledge_chunks_count > 0;
            })
            ->addColumn('processing', function($row) {
                return $row->knowledge_chunks_count == 0;
            })
            ->addColumn('status', function($row) {
                return $row->knowledge_chunks_count > 0 ? 'processed' : 'processing';
            })
            ->addColumn('action', function($row) {
                $buttons = '<div class="flex justify-center space-x-2">';
                // Verificamos permisos para cada acción
                if (Auth::user()->can('download', $row)) {
                    $buttons .= '<a href="' . route('knowledge_base.download', $row->id) . '" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-xs p-2 text-center inline-flex items-center dark:bg-blue-700 dark:hover:bg-blue-800 dark:focus:ring-blue-900" title="' . __('Descargar') . '">
                        <iconify-icon icon="heroicons:arrow-down-tray" class="w-4 h-4"></iconify-icon>
                    </a>';
                }
                if (Auth::user()->can('delete', $row)) {
                    $buttons .= '<button onclick="deletePdf(this)" data-url="' . route('knowledge_base.delete', $row->id) . '" class="text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-xs p-2 text-center inline-flex items-center dark:bg-red-700 dark:hover:bg-red-800 dark:focus:ring-red-900" title="' . __('Eliminar') . '">
                        <iconify-icon icon="heroicons:trash" class="w-4 h-4"></iconify-icon>
                    </button>';
                }
                $buttons .= '</div>';
                return $buttons;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Maneja la subida de un nuevo archivo PDF y despacha el Job de procesamiento.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleUpload(Request $request)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:20480', // 20MB
            'upload_type' => 'required|in:user,company',
        ]);

        $user = Auth::user();
        $file = $request->file('pdf');
        
        $isCompanyUpload = $request->input('upload_type') === 'company';

        if ($isCompanyUpload && !$user->can('uploadCompanyKnowledge', KnowledgeBaseFile::class)) {
            return redirect()->back()->with('error', 'No tienes permiso para subir archivos para la empresa.');
        }
        
        if (!StorageHelper::exists('knowledge_base_uploads')) {
            Storage::makeDirectory('knowledge_base_uploads');
        }
        
        $path = $file->store('knowledge_base_uploads');
        $originalName = $file->getClientOriginalName();

        $userIdForFile = $isCompanyUpload ? null : $user->id;

        $knowledgeBaseFile = KnowledgeBaseFile::create([
            'file_path' => $path,
            'original_name' => $originalName,
            'user_id' => $userIdForFile,
        ]);

        try {
            ProcessPdfForRagJob::dispatch($knowledgeBaseFile->id);
        } catch (\Exception $e) {
            Log::error('Error al despachar el Job de procesamiento de PDF: ' . $e->getMessage());
            $knowledgeBaseFile->delete();
            Storage::delete($path);
            return redirect()->back()->with('error', 'Hubo un problema al iniciar el procesamiento del PDF.');
        }

        return redirect()->back()->with('success', 'PDF subido. Se está procesando en segundo plano.');
    }
    
    /**
     * Permite la descarga de un archivo PDF con verificación de permisos.
     *
     * @param \App\Models\KnowledgeBaseFile $file
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
     */
    public function downloadPdf(KnowledgeBaseFile $file)
    {
        $this->authorize('download', $file);
        
        return StorageHelper::download($file->file_path, $file->original_name);
    }

    /**
     * Elimina un archivo PDF y sus datos relacionados.
     *
     * @param \App\Models\KnowledgeBaseFile $file
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(KnowledgeBaseFile $file)
    {
        $this->authorize('delete', $file);

        try {
            // Usamos la relación para borrar los chunks asociados
            $file->knowledgeChunks()->delete();
            
            // Eliminamos el archivo físico
            if (StorageHelper::exists($file->file_path)) {
                StorageHelper::delete($file->file_path);
            }
            
            // Eliminamos el registro de la base de datos
            $file->delete();
            
            return response()->json([
                'success' => true,
                'message' => __('Documento y datos relacionados eliminados correctamente.')
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar documento: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => __('Ha ocurrido un error al eliminar el documento.')
            ], 500);
        }
    }
}