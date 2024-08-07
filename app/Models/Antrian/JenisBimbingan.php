<?php

namespace App\Models\Antrian;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Antrian\Bimbingan;

class JenisBimbingan extends Model
{
    /**
     * Model ini mengarah ke tabel jenis bimbingan db simak baru skema antrian
     */
    use HasFactory;

    protected $table = 'antrian.jenis_bimbingan';
    protected $connection;
    protected $guarded = ['jenis_bimbingan_id'];

    public $primaryKey = 'jenis_bimbingan_id';
    public $timestamps = false;

    public function __construct()
    {
        $this->connection = config('myconfig.database.first_connection');
    }

    public function bimbingan() {
        return $this->hasMany(Bimbingan::class, 'jenis_bimbingan_id', 'jenis_bimbingan_id');
    }
}
