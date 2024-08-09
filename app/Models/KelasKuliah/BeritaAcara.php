<?php

namespace App\Models\KelasKuliah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeritaAcara extends Model
{
    /**
     * Model ini mengarah ke tabel berita_acara di db simak baru skema kuliah
     * Digunakan apabila kelas kuliah dibuka
     */
    use HasFactory;

    protected $connection;
    protected $table = 'kuliah.berita_acara';
    protected $guarded = ['berita_acara_id'];

    public $primaryKey = 'berita_acara_id';
    public $increment = true;
    public $timestamps = false;

    public function __construct() {
        $this->connection = config('myconfig.database.first_connection');
    }
}
