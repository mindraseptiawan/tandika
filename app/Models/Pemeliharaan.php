<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pemeliharaan extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'pemeliharaan';
    protected $fillable = [
        'kandang_id',
        'jenis_pakan_id',
        'umur',
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
}
