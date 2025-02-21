<?php

namespace App\Http\Controllers;

use App\Models\HostList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class HostListController extends Controller
{
    /**
     * Muestra el dashboard principal con la lista de servidores y sus datos de monitoreo.
     */
    public function index()
    {
        $user = auth()->user();
        $query = HostList::query();

        // Verificamos los permisos para mostrar hosts
        if ($user->hasPermissionTo('servermonitor show') && $user->hasPermissionTo('servermonitorbusynes show')) {
            // Mostrar tanto los hosts propios como los globales
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereNull('user_id');
            });
        } elseif ($user->hasPermissionTo('servermonitor show')) {
            // Solo hosts propios
            $query->where('user_id', $user->id);
        } elseif ($user->hasPermissionTo('servermonitorbusynes show')) {
            // Solo hosts globales
            $query->whereNull('user_id');
        } else {
            abort(403, 'No tienes permiso para ver los servidores.');
        }

        $hosts = $query->with(['hostMonitors' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }])->get();

        return view('hosts.index', compact('hosts'));
    }

    /**
     * Muestra el formulario para crear un nuevo servidor.
     */
    public function create()
    {
        return view('hosts.create');
    }

    /**
     * Almacena un nuevo servidor en la base de datos.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'name'  => 'required|string|max:255',
            'host'  => 'required|string|max:255',
            'token' => 'required|string|max:255',
        ]);

        $data = $request->only('name', 'host', 'token');

        if ($user->hasPermissionTo('servermonitor create')) {
            // El host es propio
            $data['user_id'] = $user->id;
        } elseif ($user->hasPermissionTo('servermonitorbusynes create')) {
            // El host es global (sin user_id)
            $data['user_id'] = null;
        } else {
            abort(403, 'No tienes permiso para crear un servidor.');
        }

        HostList::create($data);

        return redirect()->route('servermonitor.index')
                         ->with('success', 'Servidor creado exitosamente.');
    }

    /**
     * Muestra el formulario para editar un servidor existente.
     */
    public function edit($id)
    {
        $host = HostList::findOrFail($id);
        return view('hosts.edit', compact('host'));
    }

    /**
     * Actualiza los datos de un servidor en la base de datos.
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $host = HostList::findOrFail($id);

        $request->validate([
            'name'  => 'required|string|max:255',
            'host'  => 'required|string|max:255',
            'token' => 'required|string|max:255',
        ]);

        // Si el host tiene user_id, es propio; de lo contrario, es global.
        if ($host->user_id) {
            if (!$user->hasPermissionTo('servermonitor update') || $host->user_id != $user->id) {
                abort(403, 'No tienes permiso para actualizar este servidor.');
            }
        } else {
            if (!$user->hasPermissionTo('servermonitorbusynes update')) {
                abort(403, 'No tienes permiso para actualizar este servidor global.');
            }
        }

        $host->update($request->only('name', 'host', 'token'));

        return redirect()->route('servermonitor.index')
                         ->with('success', 'Servidor actualizado exitosamente.');
    }

    /**
     * Elimina un servidor de la base de datos.
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $host = HostList::findOrFail($id);

        if ($host->user_id) {
            if (!$user->hasPermissionTo('servermonitor delete') || $host->user_id != $user->id) {
                abort(403, 'No tienes permiso para eliminar este servidor.');
            }
        } else {
            if (!$user->hasPermissionTo('servermonitorbusynes delete')) {
                abort(403, 'No tienes permiso para eliminar este servidor global.');
            }
        }

        $host->delete();

        return redirect()->route('servermonitor.index')
                         ->with('success', 'Servidor eliminado exitosamente.');
    }
}
