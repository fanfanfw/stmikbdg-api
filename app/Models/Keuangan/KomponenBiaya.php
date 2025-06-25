<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KomponenBiaya extends Model
{
    use HasFactory;
    protected $connection;

    protected $table = 'keuangan.m_komponen_biaya';

    protected $primaryKey = 'id_komponen';

    protected $fillable = [
        'tahun_angkatan', 'id_nama_komponen', 'nama_komponen', 'kewajiban', 'ket', 'status',
    ];

    public $timestamps = true;

    public function __construct() {
        $this->connection = config('myconfig.database.first_connection');
    }
}
