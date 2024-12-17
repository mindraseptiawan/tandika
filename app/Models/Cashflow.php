<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Cashflow extends Model
{
    use LogsActivity; // Mengaktifkan pencatatan aktivitas

    // Tentukan atribut yang akan dicatat
    protected static $logAttributes = ['name', 'email'];

    // Nama log (Opsional)
    protected static $logName = 'user_activity';

    // Konfigurasi lebih lanjut untuk deskripsi log
    public function getActivitylogOptions()
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email']) // Mencatat hanya atribut tertentu
            ->setDescriptionForEvent(fn(string $eventName) => "User {$eventName}");
    }
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
