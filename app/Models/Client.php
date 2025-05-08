<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'vat_number', // NIF/CIF
        'vat_rate',   // <-- AÑADIDO AQUÍ
        'address',
        'city',
        'postal_code',
        'country',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'vat_rate' => 'decimal:2', // <-- AÑADIDO AQUÍ (para manejarlo como decimal)
    ];

    /**
     * Get the quotes for the client.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Get the projects for the client.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the invoices for the client.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the discounts associated with the client.
     */
    public function discounts(): HasMany
    {
        // Assuming you might have client-specific discounts
        // This implies a 'client_id' foreign key in your 'discounts' table
        return $this->hasMany(Discount::class);
    }
}
