<?php
// app/Models/HostList.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Passport\HasApiTokens; // Añade este trait

class HostList extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = ['host', 'token', 'name'];

    public function hostMonitors()
    {
        return $this->hasMany(HostMonitor::class, 'id_host');
    }
}
