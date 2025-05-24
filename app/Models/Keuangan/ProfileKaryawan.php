<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileKaryawan extends Model
{
    use HasFactory;

    protected $table = 'keuangan.k_karyawan';
    protected $connection;

    // fillable ->  nidn_nuptk | nik | nama | email | no_telepon | pendidikan_terakhir | alamat | tmt | no_rekening | nama_bank | id_status | id_jabatan | id_golongan | id_fungsional | created_at | updated_at | status_menikah 
    
    protected $fillable = [
        'nidn_nuptk',
        'nik',
        'nama',
        'email',
        'no_telepon',
        'pendidikan_terakhir',
        'alamat',
        'tmt',
        'no_rekening',
        'nama_bank',
        'id_status',
        'id_jabatan',
        'id_golongan',
        'id_fungsional',
        'status_menikah'
    ]
    ;
    public function __construct()
    {
        $this->connection = config('myconfig.database.first_connection');
    }

    public function potonganKaryawan(){
        return $this->hasMany(PotonganKaryawan::class,'id_karyawan');
    }

    public function pengajaranKaryawan(){
        return $this->hasMany(PengajaranKaryawan::class,'id_karyawan');
    }

    // | id_status | id_jabatan | id_golongan | id_fungsional |

    public function statusKaryawan(){
        return $this->belongsTo(StatusKaryawan::class,'id_status');
    }

    public function jabatanKaryawan(){
        return $this->belongsTo(JabatanKaryawan::class,'id_jabatan');
    }

    public function golonganJabatan(){
        return $this->belongsTo(GolonganKaryawan::class,'id_golongan');
    }

    public function fungsionalKaryawan(){
        return $this->belongsTo(FungsionalKaryawan::class,'id_fungsional');
    }
    

}
