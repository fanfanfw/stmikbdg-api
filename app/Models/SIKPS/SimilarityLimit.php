<?php

namespace App\Models\SIKPS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimilarityLimit extends Model
{
    /**
     * Model ini mengarah ke tabel similarity_limits di db simak baru skema deteksi_proposal
     */
    use HasFactory;

    protected $table = 'deteksi_proposal.similarity_limits';
    protected $connection;
    protected $guarded = ['similarity_limit_id'];

    public $primaryKey = 'similarity_limit_id';
    public $timestamps = false;

    public function __construct()
    {
        $this->connection = config('myconfig.database.first_connection');
    }
}
