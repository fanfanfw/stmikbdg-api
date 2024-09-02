<?php

namespace App\Models\Authentications;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    /**
     * DB Baru tabel login_histories
     *
     * Digunakan untuk menyimpan informasi
     * login user dan access token, serta mengontrol login ke android
     */
    use HasFactory;

    protected $table = 'login_histories';
    protected $connection;
    protected $guarded = ['login_history_id'];
    protected $primaryKey = 'login_history_id';

    public function __construct() {
        $this->connection = config('myconfig.database.first_connection');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
