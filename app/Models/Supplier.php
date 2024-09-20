<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    protected $table = 'suppliers';

    // Specify the attributes that are mass assignable
    protected $fillable = [
        'name',
        'alamat',
        'phone'
    ];

    // Relationships
    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'suplier_id', 'id');
    }
}
