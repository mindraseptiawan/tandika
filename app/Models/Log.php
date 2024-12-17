<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Log extends Model
{
    use LogsActivity;

    protected static $logAttributes = ['username', 'phone']; // Log semua atribut
    protected static $logName = 'user_log'; // Nama log

    public function getActivityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['username', 'phone'])
            ->setDescriptionForEvent(fn(string $eventName) => "User {$eventName}");
    }
}
