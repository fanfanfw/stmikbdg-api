<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajaranKaryawan extends Model
{
    use HasFactory;

    protected $table = 'keuangan.k_pengajaran';
    protected $connection;

    protected $fillable = ['id_karyawan','sks','honor_per_sks','sks_lebih','bulan'];

    public function __construct()
    {
        $this->connection = config('myconfig.database.first_connection');
    }

    public function detailSkripsiKpKaryawan(){
        return $this->hasMany(DetailSkripsiKpKaryawan::class,'id_pengajaran');
    }

    public function profileKaryawan(){
        return $this->belongsTo(ProfileKaryawan::class,'id_karyawan');
    }

    


}
