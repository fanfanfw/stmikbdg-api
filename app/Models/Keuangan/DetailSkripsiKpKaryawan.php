<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailSkripsiKpKaryawan extends Model
{
    use HasFactory;
    protected $table = 'keuangan.k_detail_skripsi_kp';
    protected $connection ;


    protected $fillable = ['id_pengajaran', 'id_skripsi_kp', 'periode_pencairan'];
    
    public function __construct()
    {
        $this->connection = config('myconfig.database.first_connection');
    }

    public function skripsiKpKaryawan(){
        return $this->belongsTo(SkripsiKpKaryawan::class, 'id_skripsi_kp');
    }

    public function pengajaranKaryawan(){
        return $this->belongsTo(PengajaranKaryawan::class, 'id_pengajaran');
    }
}
