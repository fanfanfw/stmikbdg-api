<?php

namespace App\Models\ArsipDigital;

use Illuminate\Database\Eloquent\SoftDeletes;

class DistributionRecipient extends ArsipDigitalModel
{
    use SoftDeletes;

    protected $table = 'arsip_digital.distribution_recipients';
    protected $primaryKey = 'recipient_id';
    protected $guarded = ['recipient_id'];

    protected $casts = [
        'metadata' => 'array',
    ];
}
