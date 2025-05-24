<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkripsiKpKaryawan extends Model
{
    use HasFactory;

    protected $table = 'keuangan.k_skripsi_kp';
    protected $connection ;


    protected $fillable = ['kegiatan', 'honor'];
    
    public function __construct()
    {
        $this->connection = config('myconfig.database.first_connection');
    }

    public function detailSkripsiKpKaryawan(){
        return $this->hasMany(DetailSkripsiKpKaryawan::class, 'id_skripsi_kp');
    }
}
