<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusKaryawan extends Model
{
    use HasFactory;

    protected $table = 'keuangan.k_status';
    protected $connection = 'pgsql';

    protected $fillable = ['nama', 'is_pengajar'];
}
