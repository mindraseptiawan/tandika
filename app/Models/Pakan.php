<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Pakan extends Model
{
    use HasFactory, LogsActivity;
    protected static $logAttributes = [
        'jenis',
        'sisa',
        'keterangan',
    ];
    protected static $logName = 'pakan_log';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'pakan';
    protected $fillable = [
        'jenis',
        'sisa',
        'keterangan',
    ];
    public function getActivityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'jenis',
                'sisa',
                'keterangan',
            ])
            ->setDescriptionForEvent(fn(string $eventName) => "Pakan Berhasil di{$eventName}");
    }
}
