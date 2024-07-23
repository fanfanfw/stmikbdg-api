<?php

namespace App\Models\SIKPS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PengajuanKerjaPraktekDiterimaView extends Model
{
    /**
     * Model ini mengarah ke view pengajuan kp diterima di db simak baru skema sikps
     * Digunakan untuk mengelola proses read atau select
     */
    use HasFactory;

    protected $table = 'sikps.vpengajuan_kp_diterima';
    protected $connection;

    public $timestamps = false;

    public function __construct()
    {
        $this->connection = config('myconfig.database.first_connection');
    }

    public function scopeGetPengajuanKerjaPraktek(Builder $query, $nim)
    {
        return $query->where('nim', $nim)->orderBy('created_at', 'DESC')->first();
    }
}
