<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PotonganKaryawan extends Model
{
    use HasFactory;

    protected $table = 'keuangan.k_potongan';
    protected $connection;

    protected $fillable = ['id_karyawan','id_potongan','bulan'];

    public function __construct()
    {
        $this->connection = config('myconfig.database.first_connection');
    }

    public function listPotonganKaryawan(){
        return $this->belongsTo(ListPotonganKaryawan::class, 'id_potongan');
    }

    public function profileKaryawan(){
        return $this->belongsTo(ProfileKaryawan::class, 'id_karyawan');
    }
}
