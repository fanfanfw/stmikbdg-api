<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JabatanKaryawan extends Model
{
   use HasFactory;


   protected $table = 'keuangan.k_jabatan';
   protected $connection = 'pgsql';


   protected $fillable = ['nama', 'kategori', 'honor'];
}
