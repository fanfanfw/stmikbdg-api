<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaBeasiswa extends Model
{
    use HasFactory;

    protected $table = 'keuangan.m_penerima_beasiswa';
    protected $primaryKey = 'id_penerima';
    protected $fillable = [
        'id_beasiswa',
        'mhs_id',
        // 'nominal',
        'status',
    ];

    public $increment = true;
    public $timestamps = true;

    protected $connection;

    public function __construct() {
        $this->connection = config('myconfig.database.first_connection');
    }

    public function master_beasiswa() {
        return $this->belongsTo(MasterBeasiswa::class, 'id_beasiswa', 'id_beasiswa');
    }
}
