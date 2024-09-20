<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $table = 'customers';

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
}
