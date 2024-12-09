<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Order extends Model
{
    protected $table = 'orders';
    protected $fillable = [
        'customer_id',
        'order_date',
        'status',
        'quantity',
        'alamat',
        'payment_method',
        'payment_proof',
        'payment_verified_at',
        'payment_verified_by',
        'kandang_id'
    ];


    protected $appends = ['payment_proof_url'];

    // Relationship methods remain the same...

    // New accessor to generate full URL for payment proof
    public function getPaymentProofUrlAttribute()
    {
        // Check if payment_proof exists and is not null
        if ($this->payment_proof) {
            // Use Storage facade to generate a public URL
            return Storage::url($this->payment_proof);
        }

        return null;
    }
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

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }
    public function kandang()
    {
        return $this->belongsTo(Kandang::class);
    }
}
