<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\KnowledgeBaseFile;

class KnowledgeBaseController extends Controller
{
    // Vista principal IA Memory (con datatables y formulario)
    public function index()
    {
        return view('knowledge_base.index');
    }

    // DataTable: PDFs subidos por el usuario actual
    public function userData(Request $request)
    {
        $userId = Auth::id();
        $pdfs = \App\Models\KnowledgeBaseFile::where('user_id', $userId)
            ->orderByDesc('created_at');
        return datatables()->of($pdfs)
            ->addColumn('original_name', function($row) {
                return e($row->original_name);
            })
            ->addColumn('tipo', function($row) {
                return $row->user_id ? 'Usuario' : 'Empresa';
            })
            ->addColumn('action', function($row) {
                return '<a href="' . route('knowledge_base.download', $row->id) . '" target="_blank" class="btn btn-sm btn-primary">Descargar</a>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    // DataTable: PDFs subidos como empresa (globales)
    public function companyData(Request $request)
    {
        // Verificar que el usuario tiene permiso para ver PDFs de empresa
        if (!Auth::user()->can('knowledgebase.upload.company')) {
            return datatables()->of([])->make(true);
        }
        
        // Obtener todos los PDFs de empresa (user_id es NULL)
        $pdfs = KnowledgeBaseFile::whereNull('user_id')
            ->orderByDesc('created_at');
        return datatables()->of($pdfs)
            ->addColumn('original_name', function($row) {
                return e($row->original_name);
            })
            ->addColumn('tipo', function($row) {
                return $row->user_id ? 'Usuario' : 'Empresa';
            })
            ->addColumn('action', function($row) {
                return '<a href="' . route('knowledge_base.download', $row->id) . '" target="_blank" class="btn btn-sm btn-primary">Descargar</a>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }


    // Muestra el formulario de subida
    public function showUploadForm()
    {
        return view('knowledge_base.upload');
    }

    // Maneja la subida del PDF
    public function handleUpload(Request $request)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:20480', // 20MB
            'upload_type' => 'required|in:user,company',
        ]);

        $user = Auth::user();
        $file = $request->file('pdf');
        
        // Crear el directorio si no existe
        if (!Storage::exists('knowledge_base_uploads')) {
            Storage::makeDirectory('knowledge_base_uploads');
        }
        
        // Guardar el archivo en el disco predeterminado (no en 'public')
        $path = $file->store('knowledge_base_uploads');
        $originalName = $file->getClientOriginalName();

        // Determinar si es subida de usuario o empresa
        $userId = null;
        if ($request->upload_type === 'user') {
            $userId = $user->id;
        }

        \App\Models\KnowledgeBaseFile::create([
            'file_path' => $path,
            'original_name' => $originalName,
            'user_id' => $userId,
        ]);

        // Aquí se lanzaría el Job de ingesta (lo añadiremos después)
        // ProcessPdfIngestionJob::dispatch($path, $user, $originalName);

        return redirect()->back()->with('success', 'PDF subido correctamente.');
    }
    
    /**
     * Descarga un PDF con verificación de permisos
     */
    public function downloadPdf($id)
    {
        $file = KnowledgeBaseFile::findOrFail($id);
        
        // Verificar permisos
        if ($file->user_id) {
            // Si es un PDF de usuario, solo el propietario puede descargarlo
            if ($file->user_id != Auth::id()) {
                abort(403, 'No tienes permiso para descargar este archivo.');
            }
        } else {
            // Si es un PDF de empresa, solo usuarios con permiso company pueden descargarlo
            if (!Auth::user()->can('knowledgebase.upload.company')) {
                abort(403, 'No tienes permiso para descargar archivos de empresa.');
            }
        }
        
        // Si el archivo existe, devolverlo como descarga
        $filePath = $file->file_path;
        
        // Intentar varias rutas posibles para encontrar el archivo
        $possiblePaths = [
            $filePath,                     // Ruta original
            'public/' . $filePath,         // Con prefijo public/
            str_replace('public/', '', $filePath) // Sin prefijo public/
        ];
        
        foreach ($possiblePaths as $path) {
            if (Storage::exists($path)) {
                return Storage::download($path, $file->original_name);
            }
        }
        
        // Si llegamos aquí, intentar buscar directamente en el sistema de archivos
        $fullPath = storage_path('app/' . $filePath);
        if (file_exists($fullPath)) {
            return response()->download($fullPath, $file->original_name);
        }
        
        return abort(404, 'Archivo no encontrado.');
    }
}
