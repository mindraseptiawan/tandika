<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Cashflow extends Model
{
    use LogsActivity;

    protected static $logAttributes = ['name', 'email'];

    protected static $logName = 'user_activity';

    public function getActivitylogOptions()
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email']) // Mencatat hanya atribut tertentu
            ->setDescriptionForEvent(fn(string $eventName) => "User {$eventName}");
    }
    protected $table = 'cashflows';

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
