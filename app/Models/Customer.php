<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use HasFactory, LogsActivity;
    protected $table = 'customers';
    protected static $logAttributes = ['name', 'alamat', 'phone'];
    protected static $logName = 'customer_log';
    // Specify the attributes that are mass assignable
    protected $fillable = [
        'name',
        'alamat',
        'phone'
    ];

    // Relationships
    public function sales()
    {
        return $this->hasMany(Sale::class, 'customer_id', 'id');
    }
    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'id');
    }
    public function getActivityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'alamat', 'phone'])
            ->setDescriptionForEvent(fn(string $eventName) => "Customer Berhasil di{$eventName}");
    }
}
