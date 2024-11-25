<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $table = 'sales';

    // Specify the attributes that are mass assignable
    protected $fillable = [
        'transaction_id',
        'customer_id',
        'order_id',
        'quantity',
        'price_per_unit',
        'ongkir',
        'total_price',
    ];

    // Relationship with Order
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }


    // Relationships
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }
}
