<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    protected $fillable = [
        'customer_id',
        'order_date',
        'status',
        'quantity',
        'alamat',
    ];

    // Relationship with Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    // Relationship with Sale (if you need to reference back to the sale)
    public function sales()
    {
        return $this->hasMany(Sale::class, 'order_id', 'id');
    }
}
