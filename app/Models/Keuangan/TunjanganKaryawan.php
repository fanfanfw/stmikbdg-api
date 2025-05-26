<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TunjanganKaryawan extends Model
{
    use HasFactory;

    protected $table = 'keuangan.k_tunjangan';
    protected $connection ;


    protected $fillable = ['id_karyawan', 'id_fungsional','yayasan','honor_fungsional','keluarga','makan','transportasi','lembur','bulan'];
    protected $hidden = ['created_at','updated_at'];
    
    public function __construct()
    {
        $this->connection = config('myconfig.database.first_connection');
    }

    public function karyawan(){
        return $this->toBelongs(ProfileKaryawan::class, 'id_karyawan');
    }
}

