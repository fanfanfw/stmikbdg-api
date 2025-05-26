<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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
    ];
    protected $hidden = ['created_at', 'updated_at','id_status','id_jabatan','id_fungsional','id_golongan'];


    public function __construct()
    {
        $this->connection = config('myconfig.database.first_connection');
    }

    // protected $appends = ['tunjangan']; 

    public function scopeDataKaryawanBulanIni(Builder $query)
    {
        return $query->with([
            'fungsional:id,jabatan',
            'status:id,nama,is_pengajar',
            'jabatan:id,nama,kategori,honor',
            'golongan:id,nama,honor',
            'tunjangan' => function($q){
                $q->whereMonth('bulan', now()->month)
                ->whereMonth('bulan', now()->month);
            }
            
        ]);
    }

    // public function getTunjanganAttribute()
    // {
    //     return $this->tunjanganKaryawan()
    //         ->whereMonth('bulan', now()->month)
    //         ->whereYear('bulan', now()->year)
    //         ->get();
            
    // }

    public function scopeAllDataKaryawan(Builder $query)
    {
        return $query->with([
            'fungsional:id,jabatan',
            'status:id,nama,is_pengajar',
            'jabatan:id,nama,kategori,honor',
            'golongan:id,nama,honor',
            'tunjangan'
        ]);
    }

    // public function scopeSimpleDataProfile(Builder $query)
    // {
    //     return $query->all();
    // }

    public function potongan(){
        return $this->hasMany(PotonganKaryawan::class,'id_karyawan');
    }

    public function pengajaran(){
        return $this->hasMany(PengajaranKaryawan::class,'id_karyawan');
    }

    public function tunjangan(){
        return $this->hasMany(TunjanganKaryawan::class,'id_karyawan');
    }

    // | id_status | id_jabatan | id_golongan | id_fungsional |

    public function status(){
        return $this->belongsTo(StatusKaryawan::class,'id_status');
    }

    public function jabatan(){
        return $this->belongsTo(JabatanKaryawan::class,'id_jabatan');
    }

    public function golongan(){
        return $this->belongsTo(GolonganKaryawan::class,'id_golongan');
    }

    public function fungsional(){
        return $this->belongsTo(FungsionalKaryawan::class,'id_fungsional');
    }
    

}
