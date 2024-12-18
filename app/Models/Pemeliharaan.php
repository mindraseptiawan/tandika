<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Pemeliharaan extends Model
{
    use HasFactory, LogsActivity;
    protected static $logAttributes = [
        'kandang_id',
        'jenis_pakan_id',
        'purchase_id',
        'jumlah_ayam',
        'jumlah_pakan',
        'mati',
        'keterangan',
    ];
    protected static $logName = 'pemeliharaan_log';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'pemeliharaan';
    protected $fillable = [
        'kandang_id',
        'jenis_pakan_id',
        'purchase_id',
        'jumlah_ayam',
        'jumlah_pakan',
        'mati',
        'keterangan',
    ];

    public function kandang()
    {
        return $this->belongsTo(Kandang::class, 'kandang_id', 'id');
    }

    public function pakan()
    {
        return $this->belongsTo(Pakan::class, 'jenis_pakan_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id', 'id');
    }
    public function getActivityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'kandang_id',
                'jenis_pakan_id',
                'purchase_id',
                'jumlah_ayam',
                'jumlah_pakan',
                'mati',
                'keterangan',
            ])
            ->setDescriptionForEvent(fn(string $eventName) => "Pemeliharaan Berhasil di{$eventName}");
    }
}
