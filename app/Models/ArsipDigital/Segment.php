<?php

namespace App\Models\ArsipDigital;

use Illuminate\Database\Eloquent\SoftDeletes;

class Segment extends ArsipDigitalModel
{
    use SoftDeletes;

    protected $table = 'arsip_digital.segments';
    protected $primaryKey = 'segment_id';
    protected $guarded = ['segment_id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
