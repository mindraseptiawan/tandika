<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Log extends Model
{
    use LogsActivity;

    protected static $logAttributes = ['*']; // Log semua atribut
    protected static $logName = 'system'; // Nama log

    public function getActivityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn(string $eventName) => "This model has been {$eventName}");
    }
}
