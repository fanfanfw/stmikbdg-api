<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class GolonganKaryawan extends Model
{
    use HasFactory;

   protected $table = 'keuangan.k_golongan';
   protected $connection;


   protected $fillable = ['nama', 'pendidikan_terakhir', 'honor'];


    public function scopeAllDataPendidikanTerakhir(Builder $query)
    {
        return $query->where(function ($q){
            $q->whereNotNull('pendidikan_terkahir')->where('pendidikan_terkahir', '<>', '');
        });
    }

   public function __construct() {
       $this->connection = config('myconfig.database.first_connection');
   }
}
