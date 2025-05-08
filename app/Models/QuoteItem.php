<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
class QuoteItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quote_items'; // Especificar el nombre de la tabla si no sigue la convención exacta de pluralización de Laravel

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quote_id',
        'service_id',
        'item_description',
        'quantity',
        'unit_price',
        'item_subtotal',
        'discount_id', // Si se aplica un descuento específico de la tabla discounts a esta línea
        'line_discount_percentage',
        'line_discount_amount',
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
        'line_total' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Get the quote that owns the quote item.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the service associated with the quote item (if any).
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the discount applied to this quote item (if any).
     */
    public function discount(): BelongsTo
    {
        // Esto asume que la tabla 'quote_items' tiene una columna 'discount_id'
        return $this->belongsTo(Discount::class);
    }

    // Si tuvieras una relación con invoice_items para trazar qué línea de factura
    // corresponde a esta línea de presupuesto:
    public function invoiceItem(): HasOne
    {
        return $this->hasOne(InvoiceItem::class);
    }
}
