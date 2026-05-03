<?php

namespace App\Models\ArsipDigital;

use Illuminate\Database\Eloquent\SoftDeletes;

class RequestAssignment extends ArsipDigitalModel
{
    use SoftDeletes;

    protected $table = 'arsip_digital.request_assignments';
    protected $primaryKey = 'assignment_id';
    protected $guarded = ['assignment_id'];

    protected $casts = [
        'scholarship_snapshot' => 'array',
        'metadata' => 'array',
        'is_late' => 'boolean',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];
}
