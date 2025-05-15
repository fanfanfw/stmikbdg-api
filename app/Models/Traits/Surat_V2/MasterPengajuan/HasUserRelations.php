<?php

namespace App\Models\Traits\Surat_V2\MasterPengajuan;

use App\Models\Users\User;

trait HasUserRelations
{
    public function byMhsUser()
    {
        return $this->belongsTo(User::class, 'by_is_mhs_user_id');
    }

    public function byDevUser()
    {
        return $this->belongsTo(User::class, 'by_is_dev_user_id');
    }

    public function byDoswalUser()
    {
        return $this->belongsTo(User::class, 'by_is_doswal_user_id');
    }

    public function byProdiUser()
    {
        return $this->belongsTo(User::class, 'by_is_prodi_user_id');
    }

    public function byAdminUser()
    {
        return $this->belongsTo(User::class, 'by_is_admin_user_id');
    }

    public function byDosenUser()
    {
        return $this->belongsTo(User::class, 'by_is_dosen_user_id');
    }

    public function byStaffUser()
    {
        return $this->belongsTo(User::class, 'by_is_staff_user_id');
    }

    public function byWkUser()
    {
        return $this->belongsTo(User::class, 'by_is_wk_user_id');
    }

    public function byPimpinanUser()
    {
        return $this->belongsTo(User::class, 'by_is_pimpinan_user_id');
    }

    public function byDospemUser()
    {
        return $this->belongsTo(User::class, 'by_is_dospem_user_id');
    }

    public function byMarketingUser()
    {
        return $this->belongsTo(User::class, 'by_is_marketing_user_id');
    }

    public function byAkademikUser()
    {
        return $this->belongsTo(User::class, 'by_is_akademik_user_id');
    }

    public function byBaakUser()
    {
        return $this->belongsTo(User::class, 'by_is_baak_user_id');
    }

    public function bySecretaryUser()
    {
        return $this->belongsTo(User::class, 'by_is_secretary_user_id');
    }

    public function byBendaharaUser()
    {
        return $this->belongsTo(User::class, 'by_is_bendahara_user_id');
    }

    public function byKemahasiswaanUser()
    {
        return $this->belongsTo(User::class, 'by_is_kemahasiswaan_user_id');
    }
}