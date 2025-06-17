<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KomponenFixed extends Model
{
    use HasFactory;

    protected $table = 'keuangan.m_komponen_fixed';

    protected $primaryKey = 'id_nama_komponen';

    protected $fillable = [
        'nama_komponen', 'status',
    ];

    public $timestamps = true;

    protected $connection;

    public function __construct() {
        $this->connection = config('myconfig.database.first_connection');
    }
}
