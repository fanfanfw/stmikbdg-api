<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListTunjanganKeluargaKaryawan extends Model
{
    use HasFactory;

    protected $table = 'keuangan.k_tunjangan_keluarga';
    protected $connection;

    protected $fillable = ['kode','honor'];

    public function __construct()
    {
        $this->connection = config('myconfig.database.first_connection');
    }

}
