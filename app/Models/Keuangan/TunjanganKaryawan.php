<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;


class TunjanganKaryawan extends Model
{
    use HasFactory;

    protected $table = 'keuangan.k_tunjangan';
    protected $connection ;


    protected $fillable = ['id_karyawan','yayasan','honor_fungsional','makan','transportasi','lembur','bulan'];
    protected $hidden = ['created_at','updated_at','id_keluarga'];


    public function __construct()
    { 
        $this->connection = config('myconfig.database.first_connection');
    }

    public function scopeAllDataTunjangan(Builder $query)
    {
        return $query->with([
           'tunjanganKeluarga:id,kode,honor'
            
        ]);
    }


    public function karyawan(){
        return $this->belongsTo(ProfileKaryawan::class, 'id_karyawan');
    }

    public function tunjanganKeluarga(){
        return $this->belongsTo(ListTunjanganKeluargaKaryawan::class, 'id_keluarga');
    }
}

