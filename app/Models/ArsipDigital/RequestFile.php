<?php

namespace App\Models\ArsipDigital;

use Illuminate\Database\Eloquent\SoftDeletes;

class RequestFile extends ArsipDigitalModel
{
    use SoftDeletes;

    protected $table = 'arsip_digital.request_files';
    protected $primaryKey = 'request_file_id';
    protected $guarded = ['request_file_id'];

    protected $casts = [
        'is_late' => 'boolean',
        'is_current' => 'boolean',
        'reviewed_at' => 'datetime',
    ];
}
