<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Kandang extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected static $logAttributes = ['nama_kandang', 'operator', 'kapasitas', 'jumlah_real', 'lokasi', 'status'];
    protected static $logName = 'kandang_log';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'kandang';
    protected $fillable = [
        'nama_kandang',
        'operator',
        'kapasitas',
        'jumlah_real',
        'lokasi',
        'status',
    ];

    public function pemeliharaans()
    {
        return $this->hasMany(Pemeliharaan::class, 'kandang_id', 'id');
    }
    public function getActivityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama_kandang', 'operator', 'kapasitas', 'jumlah_real', 'lokasi', 'status'])
            ->setDescriptionForEvent(fn(string $eventName) => "Kandang has been {$eventName}");
    }
}
