<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'service_id',
        'client_id',
        'start_date',
        'end_date',
        'is_active',
        'minimum_quantity',
        'minimum_purchase_amount',
        'code',
        'usage_limit',
        'used_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:2',
        'minimum_purchase_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the service that owns the discount (if any).
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the client that owns the discount (if any).
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the quote items that use this discount.
     * This assumes a discount_id foreign key in the quote_items table.
     */
    public function quoteItems(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    /**
     * Get the quotes that use this discount globally.
     * This assumes a discount_id foreign key in the quotes table.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    // You might also want a relationship for invoice_items if discounts can be applied directly there
    // public function invoiceItems(): HasMany
    // {
    //     return $this->hasMany(InvoiceItem::class);
    // }
}
