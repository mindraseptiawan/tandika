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

    public function kandang()
    {
        return $this->belongsTo(Kandang::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function scopeStockIn($query)
    {
        return $query->where('type', 'in');
    }

    public function scopeStockOut($query)
    {
        return $query->where('type', 'out');
    }

    // Hapus metode sale() dan purchase()
}
