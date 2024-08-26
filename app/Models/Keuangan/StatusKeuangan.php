<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusKeuangan extends Model
{
    /**
     * model ini digunakan untuk table status_keuangan
     */
    use HasFactory;

    public $table = 'status_keuangan';
    public $connection;

    public $primaryKey = 'sts_keuangan_id';
    public $timestamps = false;

    public function __construct() {
        $this->connection = config('myconfig.database.second_connection');
    }

    public function scopeGetStatus(Builder $query, $tahunId, $mhsId) {
        return $query->where('tahun_id', $tahunId)
            ->where('mhs_id', $mhsId)
            ->first();
    }
}
