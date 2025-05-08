<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'invoice_items'; // Especificar el nombre de la tabla

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'service_id',
        // 'quote_item_id', // Descomentar si se implementa la relación directa con quote_items
        'item_description',
        'quantity',
        'unit_price',
        'item_subtotal',
        'line_discount_percentage',
        'line_discount_amount',
        'tax_rate',
        'tax_amount_per_item',
        'line_tax_total',
        'line_total',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'item_subtotal' => 'decimal:2',
        'line_discount_percentage' => 'decimal:2',
        'line_discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount_per_item' => 'decimal:2',
        'line_tax_total' => 'decimal:2',
        'line_total' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Get the invoice that owns the invoice item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the service associated with the invoice item (if any).
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the quote item that this invoice item might originate from.
     * Descomentar si se implementa la columna 'quote_item_id' en la tabla 'invoice_items'.
     */
    public function quoteItem(): BelongsTo
    {
        return $this->belongsTo(QuoteItem::class);
    }
}
