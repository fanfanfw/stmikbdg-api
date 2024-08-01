<?php

namespace App\Models\Antrian;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisSidang extends Model
{
    /**
     * Model ini mengarah ke tabel jenis sidang db simak baru skema antrian
     */
    use HasFactory;

    protected $table = 'antrian.jenis_sidang';
    protected $connection;
    protected $guarded = ['jenis_sidang_id'];

    public $primaryKey = 'jenis_sidang_id';
    public $timestamps = false;

    public function __construct()
    {
        $this->connection = config('myconfig.database.first_connection');
    }

    public function sidang() {
        return $this->hasMany(Sidang::class, 'jenis_sidang_id', 'jenis_sidang_id');
    }
}
