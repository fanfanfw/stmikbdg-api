<?php

namespace App\Models\ArsipDigital;

class OfficialDocument extends ArsipDigitalModel
{
    protected $table = 'arsip_digital.official_documents';

    protected $primaryKey = 'official_document_id';

    protected $guarded = ['official_document_id'];

    protected $casts = [
        'semester' => 'integer',
        'subject_user_id' => 'integer',
        'subject_mhs_id' => 'integer',
        'academic_snapshot' => 'array',
        'snapshot_captured_at' => 'datetime',
        'issued_by_user_id' => 'integer',
        'issued_at' => 'datetime',
    ];

    public function file()
    {
        return $this->belongsTo(ArchiveFile::class, 'file_id', 'file_id');
    }
}
