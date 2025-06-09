<?php

namespace App\Http\Controllers;

use App\Models\DigitalCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class DigitalCertificateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        \Log::info('Accediendo al método index de DigitalCertificateController');
        
        try {
            $this->authorize('viewAny', DigitalCertificate::class);
            \Log::info('Usuario autorizado para ver certificados');
            
            $certificates = DigitalCertificate::latest()->paginate(10);
            \Log::info('Certificados encontrados: ' . $certificates->count());
            
            return view('digital-certificates.index', compact('certificates'));
        } catch (\Exception $e) {
            \Log::error('Error en DigitalCertificateController@index: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', DigitalCertificate::class);
        
        return view('digital-certificates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', DigitalCertificate::class);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'certificate' => 'required|file|max:5120', // 5MB max
            'password' => 'required|string|min:4',
            'expires_at' => 'nullable|date|after:today',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        // Validación manual de la extensión del archivo
        $file = $request->file('certificate');
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, ['pfx', 'p12'])) {
            return back()
                ->withInput()
                ->withErrors(['certificate' => 'El archivo debe ser de tipo PFX o P12.']);
        }
        
        try {
            // Almacenar el archivo del certificado
            $file = $request->file('certificate');
            $fileName = 'certificates/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            
            // Guardar el archivo en el almacenamiento privado
            $path = $file->storeAs('private', $fileName);
            
            // Crear el registro en la base de datos
            $certificate = DigitalCertificate::create([
                'name' => $validated['name'],
                'file_path' => $path,
                'password' => $validated['password'],
                'expires_at' => $validated['expires_at'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);
            
            return redirect()
                ->route('digital-certificates.index')
                ->with('message', 'Certificate uploaded successfully.')
                ->with('type', 'success');
                
        } catch (\Exception $e) {
            Log::error('Error uploading certificate: ' . $e->getMessage());
            
            // Si hay un error, eliminar el archivo si se subió
            if (isset($path) && Storage::exists($path)) {
                Storage::delete($path);
            }
            
            return back()
                ->withInput()
                ->with('message', 'Error uploading certificate: ' . $e->getMessage())
                ->with('type', 'error');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(DigitalCertificate $digitalCertificate)
    {
        $this->authorize('view', $digitalCertificate);
        
        return view('digital-certificates.show', compact('digitalCertificate'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DigitalCertificate $digitalCertificate)
    {
        $this->authorize('update', $digitalCertificate);
        
        return view('digital-certificates.edit', compact('digitalCertificate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DigitalCertificate $digitalCertificate)
    {
        $this->authorize('update', $digitalCertificate);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'certificate' => 'nullable|file|max:5120',
            'password' => 'nullable|string|min:4',
            'expires_at' => 'nullable|date|after:today',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        // Validación manual de la extensión del archivo si se proporciona uno nuevo
        if ($request->hasFile('certificate')) {
            $file = $request->file('certificate');
            $extension = strtolower($file->getClientOriginalExtension());
            
            if (!in_array($extension, ['pfx', 'p12'])) {
                return back()
                    ->withInput()
                    ->withErrors(['certificate' => 'El archivo debe ser de tipo PFX o P12.']);
            }
        }
        
        try {
            $updateData = [
                'name' => $validated['name'],
                'expires_at' => $validated['expires_at'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_active' => $validated['is_active'] ?? $digitalCertificate->is_active,
            ];
            
            // Actualizar contraseña si se proporciona
            if (!empty($validated['password'])) {
                $updateData['password'] = $validated['password'];
            }
            
            // Actualizar archivo si se proporciona
            if ($request->hasFile('certificate')) {
                // Eliminar el archivo antiguo
                if (Storage::exists($digitalCertificate->file_path)) {
                    Storage::delete($digitalCertificate->file_path);
                }
                
                // Subir el nuevo archivo
                $file = $request->file('certificate');
                $fileName = 'certificates/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('private', $fileName);
                
                $updateData['file_path'] = $path;
            }
            
            $digitalCertificate->update($updateData);
            
            return redirect()
                ->route('digital-certificates.index')
                ->with('message', 'Certificate updated successfully.')
                ->with('type', 'success');
                
        } catch (\Exception $e) {
            Log::error('Error updating certificate: ' . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('message', 'Error updating certificate: ' . $e->getMessage())
                ->with('type', 'error');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DigitalCertificate $digitalCertificate)
    {
        $this->authorize('delete', $digitalCertificate);
        
        try {
            // Eliminar el archivo del almacenamiento
            if (Storage::exists($digitalCertificate->file_path)) {
                Storage::delete($digitalCertificate->file_path);
            }
            
            // Eliminar el registro de la base de datos
            $digitalCertificate->delete();
            
            return redirect()
                ->route('digital-certificates.index')
                ->with('message', 'Certificate deleted successfully.')
                ->with('type', 'success');
                
        } catch (\Exception $e) {
            Log::error('Error deleting certificate: ' . $e->getMessage());
            
            return back()
                ->with('message', 'Error deleting certificate: ' . $e->getMessage())
                ->with('type', 'error');
        }
    }
    
    /**
     * Download the certificate file.
     */
    public function download(DigitalCertificate $digitalCertificate)
    {
        $this->authorize('view', $digitalCertificate);
        
        if (!Storage::exists($digitalCertificate->file_path)) {
            abort(404, 'Certificate file not found');
        }
        
        return Storage::download($digitalCertificate->file_path, "certificate-{$digitalCertificate->id}.pfx");
    }
}
