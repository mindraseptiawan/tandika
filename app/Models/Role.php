<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YourModelName extends Model
{
    use HasFactory;

    // Specify the table name if it's not the plural form of the model name
    protected $table = 'roles'; // Replace with your actual table name

    // Specify the primary key if it's not 'id'
    protected $primaryKey = 'id';

    // Disable timestamps if you don't want them automatically managed
    public $timestamps = true; // Set to false if you don't want created_at and updated_at

    // Define the fillable properties
    protected $fillable = [
        'name',
        'guard_name',
    ];
    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }
}
