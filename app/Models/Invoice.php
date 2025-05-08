<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'quote_id',
        'project_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'currency',
        'payment_terms',
        'notes_to_client',
        'internal_notes',
        'verifactu_id', // Para Veri*factu
        'verifactu_qr_code_data', // Para Veri*factu
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the client that owns the invoice.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the quote that originated the invoice (if any).
     */
    public function quote(): BelongsTo
    {
        // Asume que 'invoices' tiene 'quote_id'
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the project associated with the invoice (if any).
     */
    public function project(): BelongsTo
    {
        // Asume que 'invoices' tiene 'project_id'
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the items for the invoice.
     * Renombrado a 'items' para consistencia, podrías usar 'invoiceItems'.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
