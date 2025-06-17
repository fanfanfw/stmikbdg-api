<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterBeasiswa extends Model
{
    use HasFactory;

    protected $table = 'keuangan.m_beasiswa';
    protected $primaryKey = 'id_beasiswa';
    protected $fillable = [
        'nama_beasiswa',
        'id_thn_akademik',
        'file_sk',
        // 'semester',
        // 'berlaku_mulai',
        // 'berlaku_sampai',
        'status',
    ];

    protected $connection;

    public function __construct() {
        $this->connection = config('myconfig.database.first_connection');
    }

    public function penerima_beasiswa() {
        return $this->hasMany(PenerimaBeasiswa::class, 'id_beasiswa', 'id_beasiswa');
    }
}
