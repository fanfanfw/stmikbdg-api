<?php

namespace App\Models\Users;

use App\Models\Surat_V2\Pengajuan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    /**
     * Model ini digunakan untuk tabel users
     * untuk melakukan proses-proses Create, Update, Delete.
     *
     * Koneksi database terhubung ke 'simak_stmikbdg' tabel users.
     *
     * Saat ini masih menggunakan dua database berbeda sehingga
     * belum mendukung relasi antar tabel users->dosen dan users->mahasiswa
     */
    use HasFactory, Notifiable, HasApiTokens;

    protected $connection;
    protected $table = 'users';
    protected $guarded = ['id'];
    protected $hidden = ['password'];
    protected $casts = [
        'password' => 'hashed',
    ];

    public function __construct() {
        $this->connection = config('myconfig.database.first_connection');
    }

    public function surat_v2_pengajuan_mahasiswa() {
        return $this->hasMany(Pengajuan::class, 'user_id', 'id');
    }
}
