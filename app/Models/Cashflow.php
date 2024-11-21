<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cashflow extends Model
{
    protected $table = 'cashflows';

    // Specify the attributes that are mass assignable
    protected $fillable = [
        'transaction_id',
        'type',
        'amount',
        'balance',
    ];

    // Relationships
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }
}
