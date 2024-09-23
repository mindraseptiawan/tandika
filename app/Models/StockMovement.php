<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'kandang_id',
        'type',
        'quantity',
        'reason',
        'reference_id',
        'reference_type',
        'notes',
    ];

    /**
     * Get the kandang that owns the stock movement.
     */
    public function kandang()
    {
        return $this->belongsTo(Kandang::class);
    }

    /**
     * Get the owning referenceable model.
     */
    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include stock ins.
     */
    public function scopeStockIn($query)
    {
        return $query->where('type', 'in');
    }

    /**
     * Scope a query to only include stock outs.
     */
    public function scopeStockOut($query)
    {
        return $query->where('type', 'out');
    }

    /**
     * Get the sale associated with the stock movement.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class, 'reference_id')
            ->where('reference_type', Sale::class);
    }

    /**
     * Get the purchase associated with the stock movement.
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'reference_id')
            ->where('reference_type', Purchase::class);
    }
}
