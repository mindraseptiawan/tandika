<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $table = 'transactions';

    // Specify the attributes that are mass assignable
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'keterangan'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function purchases()
    {
        return $this->hasOne(Purchase::class, 'transaction_id', 'id');
    }

    public function sales()
    {
        return $this->hasOne(Sale::class, 'transaction_id', 'id');
    }
    // Transaction.php

    public function cashflow()
    {
        return $this->hasOne(Cashflow::class, 'transaction_id', 'id');
    }
}
