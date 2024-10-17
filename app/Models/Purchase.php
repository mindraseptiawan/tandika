<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $table = 'purchases';

    // Specify the attributes that are mass assignable
    protected $fillable = [
        'transaction_id',
        'supplier_id',
        'quantity',
        'price_per_unit',
        'total_price',
        'kandang_id'
    ];

    // Relationships
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }
    public function kandang()
    {
        return $this->belongsTo(Kandang::class, 'kandang_id', 'id');
    }
}
