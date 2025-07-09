<?php

namespace App\Policies;

use App\Models\KnowledgeBaseFile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class KnowledgeBaseFilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true; // Todos los usuarios autenticados pueden ver la lista
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\KnowledgeBaseFile  $file
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, KnowledgeBaseFile $file)
    {
        // Si es un archivo personal, solo el propietario puede verlo
        if ($file->user_id) {
            return $user->id === $file->user_id;
        }
        
        // Si es un archivo de empresa, el usuario debe tener permiso específico
        return $user->can('viewCompanyKnowledge');
    }

    /**
     * Determine whether the user can download the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\KnowledgeBaseFile  $file
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function download(User $user, KnowledgeBaseFile $file)
    {
        // Si es un archivo personal, solo el propietario puede descargarlo
        if ($file->user_id) {
            return $user->id === $file->user_id;
        }
        
        // Si es un archivo de empresa, el usuario debe tener permiso específico
        return $user->can('download');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\KnowledgeBaseFile  $file
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, KnowledgeBaseFile $file)
    {
        // Si es un archivo personal, solo el propietario puede eliminarlo
        if ($file->user_id) {
            return $user->id === $file->user_id;
        }
        
        // Si es un archivo de empresa, el usuario debe tener permiso específico
        return $user->can('delete');
    }

    /**
     * Determine whether the user can upload company knowledge.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function uploadCompanyKnowledge(User $user)
    {
        return $user->can('knowledgebase.upload.company');
    }
}
