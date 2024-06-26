<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use ProtoneMedia\LaravelVerifyNewEmail\MustVerifyNewEmail;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Intervention\Image\Facades\Image;



class User extends Authenticatable implements MustVerifyEmail, HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, MustVerifyNewEmail, InteractsWithMedia;

    protected array $guard_name = ['sanctum', 'web'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'phone',
        'post_code',
        'city',
        'country',
        'photo',
        'address',
        'document_number',
        'job_position_id',
        'type_of_contract_id',
        'birthdate',
        'pin',

    ];
    public function typeOfContract()
    {
        return $this->belongsTo(TypeOfContract::class);
    }
    public function jobPosicion()
    {
        return $this->belongsTo(JobPosicion::class);
    }
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function registerMediaConversions(Media $media = null): void
    {
        // Verifica si hay medios disponibles en la colección
        if ($this->getMedia('your-collection')->isNotEmpty()) {
            // Obtiene la ruta de la primera imagen de la colección
            $imagePath = $this->getFirstMedia('your-collection')->getPath();

            // Abre la imagen utilizando Intervention Image
            $image = Image::make($imagePath);

            // Aplica un ajuste de recorte para que la imagen tenga un tamaño de 300x300 píxeles
            $image->fit(300, 300);

            // Guarda la imagen manipulada
            $image->save($imagePath);
        }
    }

    /**
     * Local scope to exclude auth user
     * @param $query
     * @return mixed
     */
    public function scopeWithoutAuthUser($query): mixed
    {
        return $query->where('id', '!=', auth()->id());
    }

    /**
     * Local scope to exclude super admin
     * @param $query
     * @return mixed
     */
    public function scopeWithoutSuperAdmin($query): mixed
    {
        return $query->where('id', '!=', 1);
    }
    public function timeControlRecords()
    {
        return $this->hasMany('App\Models\TimeControl');
    }

    public function getLastStatusId()
    {
        $lastStatus = $this->timeControlRecords()->latest('created_at')->first();
        return $lastStatus ? $lastStatus->time_control_status_id : null;
    }

    public function getAllowedButtons()
{

    $currentStatusId = $this->getLastStatusId();

    if (!$currentStatusId) {
        return TimeControlStatus::where('table_name', 'Start Workday')->pluck('id');
    }

    // Obtener las reglas permitidas
    $rules = TimeControlStatusRules::where('time_control_status_id', $currentStatusId)
        ->pluck('permission_id');

    // Obtener el último registro con el estado actual y la fecha de creación más reciente
    $lastEntry = TimeControl::where('time_control_status_id', $currentStatusId)
        ->whereNotNull('created_at')
        ->orderBy('created_at', 'desc')
        ->first();

    // Si no hay un registro con el estado actual, devolver permisos para iniciar la jornada
    if (!$lastEntry) {
        return TimeControlStatus::where('table_name', 'Start Workday')->pluck('id');
    }

    // Devolver permisos basados en el ID del último registro
    return TimeControlStatus::whereIn('id', $rules)->pluck('id');
}


    public function isValidStatus($statusId)
    {
        return TimeControlStatus::where('id', $statusId)->exists();
    }


}
