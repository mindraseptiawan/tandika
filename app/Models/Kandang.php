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
    use HasFactory, SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['name', 'email'];

    protected static $logName = 'user_activity';
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
    public function getActivitylogOptions()
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email']) // Mencatat hanya atribut tertentu
            ->setDescriptionForEvent(fn(string $eventName) => "User {$eventName}");
    }
}
