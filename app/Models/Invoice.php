<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Facades\Activity; // Import added

class Invoice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'client_id',
        'quote_id',
        'project_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'discount_amount',
        'irpf', // Porcentaje de retención IRPF
        'tax_amount',
        'total_amount',
        'currency',
        'payment_terms',
        'notes_to_client',
        'internal_notes',
        'verifactu_id', // Para Veri*factu
        'verifactu_qr_code_data', // Para Veri*factu
        'verifactu_hash', // Hash de la factura
        'verifactu_signature', // Firma digital
        'verifactu_timestamp', // Fecha de generación
        'discount_id',
        'is_locked',
        'irpf_amount', // Monto de retención IRPF
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
        'irpf_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'verifactu_timestamp' => 'datetime',
        'is_locked' => 'boolean',
    ];
    public function lock()
    {
        if ($this->isLocked()) {
            return true;
        }

        $this->update(['is_locked' => true]);
        $this->logAction('locked');
        return true;
    }

    /**
     * Desbloquea la factura (solo super-admin)
     */
    public function unlock()
    {
        if (!auth()->user()?->hasRole('super-admin')) {
            throw new \Exception('No tienes permiso para desbloquear facturas');
        }

        $this->update(['is_locked' => false]);
        $this->logAction('unlocked');
        return true;
    }

    /**
     * Verifica si la factura está bloqueada
     */
    public function isLocked()
    {
        return (bool) $this->is_locked || !is_null($this->verifactu_hash);
    }

    /**
     * Verifica si la factura puede ser editada
     */
    public function isEditable()
    {
        if (auth()->user()?->hasRole('super-admin')) {
            return true;
        }

        return !$this->isLocked();
    }

    /**
     * Lanza una excepción si la factura está bloqueada
     */
    public function checkIfEditable()
    {
        if (!$this->isEditable()) {
            throw new \App\Exceptions\InvoiceLockedException(
                'Esta factura está bloqueada y no puede ser modificada.'
            );
        }
    }

    /**
     * Sobrescribir el método save para validar antes de guardar
     */
    public function save(array $options = [])
    {
        if ($this->exists && !auth()->user()?->hasRole('super-admin')) {
            $this->checkIfEditable();
        }

        return parent::save($options);
    }

    /**
     * Sobrescribir el método delete para validar antes de eliminar
     */
    public function delete()
    {
        if (!auth()->user()?->hasRole('super-admin')) {
            $this->checkIfEditable();
        }

        return parent::delete();
    }

    /**
     * Eventos del modelo
     */
    protected static function booted()
    {
        static::updating(function ($invoice) {
            if ($invoice->isLocked() && !auth()->user()?->hasRole('super-admin')) {
                throw new \App\Exceptions\InvoiceLockedException(
                    'No se puede modificar una factura bloqueada.'
                );
            }
        });

        // Registrar acción de auditoría
        static::updated(function ($invoice) {
            if ($invoice->wasChanged('is_locked')) {
                $action = $invoice->is_locked ? 'locked' : 'unlocked';
                $invoice->logAction($action);
            }
        });
    }
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
     * Registra una acción realizada en la factura para auditoría
     *
     * @param string $action Nombre de la acción (ej: 'locked', 'unlocked', 'signed')
     * @return void
     */
    public function logAction(string $action): void
    {
        // Implementación simple de registro sin depender del paquete activity log
        \Log::info("Invoice #{$this->invoice_number} {$action} by user ID: " . (auth()->user() ? auth()->user()->id : 'system'));
    }

    /**
     * Get the items for the invoice.
     * Renombrado a 'items' para consistencia, podrías usar 'invoiceItems'.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the payments for the invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the user that created the invoice.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
