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
        // Nuevos campos IMAP:
        'imap_host',
        'imap_port',
        'imap_encryption',
        'imap_username',
        'imap_password',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        // Ocultamos la contraseña IMAP para mayor seguridad
        'imap_password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
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
     * Local scope to exclude auth user.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithoutAuthUser($query): mixed
    {
        return $query->where('id', '!=', auth()->id());
    }

    /**
     * Local scope to exclude super admin.
     *
     * @param Builder $query
     * @return Builder
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
        // Verificar si el usuario tiene el permiso 'timecontrolstatus index'
        if (!$this->hasPermissionTo('timecontrolstatus index')) {
            // Si no tiene el permiso, no se muestran botones
            return collect([]);
        }

        $currentStatusId = $this->getLastStatusId();

        if (!$currentStatusId) {
            return TimeControlStatus::where('table_name', 'Start Workday')->pluck('id');
        }

        // Obtener las reglas permitidas para el estado actual
        $rules = TimeControlStatusRules::where('time_control_status_id', $currentStatusId)
            ->pluck('permission_id');

        // Obtener el último registro con el estado actual y la fecha de creación más reciente
        $lastEntry = TimeControl::where('time_control_status_id', $currentStatusId)
            ->whereNotNull('created_at')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastEntry) {
            return TimeControlStatus::where('table_name', 'Start Workday')->pluck('id');
        }

        return TimeControlStatus::whereIn('id', $rules)->pluck('id');
    }

    public function isValidStatus($statusId)
    {
        return TimeControlStatus::where('id', $statusId)->exists();
    }

    public function shiftDays()
    {
        return $this->belongsToMany(ShiftDay::class, 'shift_day_user', 'user_id', 'shift_day_id')
                    ->using(ShiftDayUser::class)
                    ->withTimestamps();
    }
}

