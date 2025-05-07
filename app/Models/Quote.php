<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quote extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'quote_number',
        'quote_date',
        'expiry_date',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'terms_and_conditions',
        'notes_to_client',
        'internal_notes',
        'discount_id', // Para el descuento global del presupuesto
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quote_date' => 'date',
        'expiry_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the client that owns the quote.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the items for the quote.
     * Renombrado a 'items' para ser más genérico, pero podrías usar 'quoteItems'.
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    /**
     * Get the project associated with the quote.
     * Un presupuesto puede generar un proyecto.
     */
    public function project(): HasOne // O HasMany si un presupuesto puede generar múltiples proyectos
    {
        // Esto asume que la tabla 'projects' tiene una columna 'quote_id'
        return $this->hasOne(Project::class);
    }

    /**
     * Get the invoices associated with the quote.
     * Un presupuesto puede tener varias facturas (ej. facturación parcial).
     */
    public function invoices(): HasMany
    {
        // Esto asume que la tabla 'invoices' tiene una columna 'quote_id'
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the global discount applied to the quote.
     */
    public function discount(): BelongsTo
    {
        // Esto asume que la tabla 'quotes' tiene una columna 'discount_id'
        return $this->belongsTo(Discount::class);
    }
}
