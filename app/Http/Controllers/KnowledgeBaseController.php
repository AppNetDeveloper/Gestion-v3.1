<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\KnowledgeBaseFile;
use App\Models\User;
use App\Jobs\ProcessPdfForRagJob; // Importamos el Job para el procesamiento
use App\Helpers\StorageHelper; // Importamos nuestro helper personalizado

class KnowledgeBaseController extends Controller
{
    /**
     * Muestra la vista principal de la base de conocimiento con las tablas.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('knowledge_base.index');
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
            ->orderByDesc('created_at');
            
        return datatables()->of($pdfs)
            ->addColumn('original_name', function($row) {
                return e($row->original_name);
            })
            ->addColumn('tipo', function($row) {
                return '<span class="badge badge-primary">Personal</span>';
            })
            ->addColumn('action', function($row) {
                $downloadUrl = route('knowledge_base.download', $row->id);
                $deleteUrl = route('knowledge_base.delete', $row->id);
                // Usamos un botón con un data attribute para que JavaScript lo maneje
                return '
                    <a href="' . $downloadUrl . '" target="_blank" class="btn btn-sm btn-info">Descargar</a>
                    <button onclick="deleteItem(this)" data-url="' . $deleteUrl . '" class="btn btn-sm btn-danger">Eliminar</button>
                ';
            })
            ->rawColumns(['action', 'tipo'])
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
            ->orderByDesc('created_at');

        return datatables()->of($pdfs)
            ->addColumn('original_name', function($row) {
                return e($row->original_name);
            })
            ->addColumn('tipo', function($row) {
                return '<span class="badge badge-success">Empresa</span>';
            })
            ->addColumn('action', function($row) {
                $buttons = '';
                // Verificamos permisos para cada acción
                if (Auth::user()->can('download', $row)) {
                    $buttons .= '<a href="' . route('knowledge_base.download', $row->id) . '" target="_blank" class="btn btn-sm btn-info">Descargar</a> ';
                }
                if (Auth::user()->can('delete', $row)) {
                    $buttons .= '<button onclick="deleteItem(this)" data-url="' . route('knowledge_base.delete', $row->id) . '" class="btn btn-sm btn-danger">Eliminar</button>';
                }
                return $buttons;
            })
            ->rawColumns(['action', 'tipo'])
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

        // Usamos la relación para borrar los chunks asociados
        $file->knowledgeChunks()->delete();

        StorageHelper::delete($file->file_path);

        $file->delete();

        return redirect()->route('knowledge_base.index')->with('success', 'PDF y datos relacionados eliminados correctamente.');
    }
}