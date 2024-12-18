<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Order extends Model
{
    use LogsActivity;
    protected static $logAttributes = ['order_date', 'status', 'quantity',];
    protected static $logName = 'order_log';
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
    public function getActivityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'order_date',
                'status',
                'quantity',
            ])
            ->setDescriptionForEvent(fn(string $eventName) => "Order Berhasil di{$eventName}");
    }
}
