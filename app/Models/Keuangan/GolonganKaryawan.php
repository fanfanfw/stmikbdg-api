<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GolonganKaryawan extends Model
{
    use HasFactory;

   protected $table = 'keuangan.k_jabatan';
   protected $connection;


   protected $fillable = ['nama', 'pendidikan_terakhir', 'honor'];

   public function __construct() {
       $this->connection = config('myconfig.database.first_connection');
   }
}
