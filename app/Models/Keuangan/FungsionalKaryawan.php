<?php

namespace App\Models\Keuangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FungsionalKaryawan extends Model
{
    use HasFactory;
    protected $table = 'keuangan.k_fungsional';
    protected $connection;
 
 
    protected $fillable = ['jabatan'];
 
    public function __construct() {
        $this->connection = config('myconfig.database.first_connection');
    }
}
