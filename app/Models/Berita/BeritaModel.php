<?php

namespace App\Models\Wisuda;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanWisuda extends Model
{
    /**
     * Model ini mengarah ke tabel Berita di db simak baru skema wisuda
     * Digunakan untuk mengelola proses CUD
     */
    use HasFactory;

    protected $table = 'berita.berita_acara';
    protected $connection;
    protected $guarded = ['berita_id'];

    public $primaryKey = 'berita_id';
    public $timestamps = false;

    public function __construct() {
        $this->connection = config('myconfig.database.first_connection');
    }
}
