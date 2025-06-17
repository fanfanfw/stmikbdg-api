<?php

namespace App\Models\SIKPS;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterTahunAkademik extends Model
{
    protected $table = 'sikps.tahun_ajaran';
    protected $connection;

    public $primaryKey = 'id';
    protected $fillable = [
        'nama',
        'tahun_awal',
        'tahun_akhir',
        'semester',
        'status'
    ];
    public $increment = true;
    public $timestamps = true;

    public function __construct() {
        $this->connection = config('myconfig.database.first_connection');
    }

    public function scopeGetAll(Builder $query, array $filters = []){
        foreach ($filters as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $query->where($key, $value);
            }
        }
    
        return $query->get();
    }

    public function dospem_pembimbing_mahasiswa() {
        return $this->hasMany(DospemPembimbingMahasiswa::class, 'tahun_ajaran_id', 'id');
    }
}
