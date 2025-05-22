<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListPotonganKaryawan extends Model
{
    use HasFactory;

    protected $table = 'keuangan.k_list_potongan';
    protected $connection;

    protected $fillable = ['nama','nominal'];

    public function __construct()
    {
        $this->connection = config('myconfig.database.first_connection');
    }

    public function potonganKaryawan()
    {
        return $this->hasMany(PotonganKaryawan::class, 'id_potongan');
    }
}
