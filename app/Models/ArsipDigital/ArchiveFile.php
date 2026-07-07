<?php

namespace App\Models\ArsipDigital;

use Illuminate\Database\Eloquent\SoftDeletes;

class ArchiveFile extends ArsipDigitalModel
{
    use SoftDeletes;

    protected $table = 'arsip_digital.files';
    protected $primaryKey = 'file_id';
    protected $guarded = ['file_id'];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'version_number' => 'integer',
        'is_current' => 'boolean',
        'metadata' => 'array',
    ];

    public function requestFile()
    {
        return $this->hasOne(RequestFile::class, 'file_id', 'file_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }
}
