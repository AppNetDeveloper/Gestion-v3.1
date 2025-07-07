<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $user = auth()->user();
        $token = null;
        
        // Obtener el token más reciente del usuario
        $latestToken = $user->tokens()->latest()->first();
        
        if ($latestToken) {
            // Para mostrar el token completo necesitamos recrearlo
            // ya que el token en texto plano no se almacena en la base de datos
            $user->tokens()->where('id', '!=', $latestToken->id)->delete();
            $user->tokens()->delete();
            $newToken = $user->createToken('default-token');
            $token = $newToken->plainTextToken;
        }
        
        return view('profiles.index', compact('token'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateProfileRequest  $request
     * @param  User  $user
     * @return RedirectResponse
     */
    public function update(UpdateProfileRequest $request, User $user)
    {
        $user->update($request->safe(['name', 'phone', 'post_code', 'city', 'country']));

        //  sends verification email if email has changed
        if( $user->email !== $request->validated('email') ) {
            $user->newEmail($request->validated('email'));
        }

        if( $request->hasFile('photo')) {
            $user->clearMediaCollection('profile-image');
            $user->addMediaFromRequest('photo')->toMediaCollection('profile-image');
        }

        return to_route('profiles.index')->with('message', 'Profile updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
    
    /**
     * Regenera el token de API del usuario autenticado
     * 
     * @return RedirectResponse
     */
    public function regenerateToken()
    {
        $user = auth()->user();
        
        // Revocar todos los tokens existentes del usuario
        $user->tokens()->delete();
        
        // Crear un nuevo token
        $token = $user->createToken('default-token');
        
        return redirect()->back()->with([
            'message' => 'Token regenerado correctamente',
            'token' => $token->plainTextToken
        ]);
    }
}
