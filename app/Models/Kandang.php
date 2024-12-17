<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kandang extends Model
{
    use HasFactory, SoftDeletes;


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
}
