<?php

namespace App\Services\ArsipDigital;

use App\Models\KRS\NilaiAkhirView;
use App\Models\Users\MahasiswaView;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AcademicDocumentDataService
{
    public function transcriptForStudent(int $mhsId): array
    {
        $student = MahasiswaView::query()
            ->where('mhs_id', $mhsId)
            ->first();

        if (! $student) {
            throw new HttpException(404, 'Mahasiswa tidak ditemukan.');
        }

        $records = NilaiAkhirView::getNilaiAkhirByMhsId($mhsId)
            ->map(function ($record): array {
                $course = $record->matakuliah;

                return [
                    'mk_id' => $record->mk_id,
                    'kd_mk' => trim((string) ($course->kd_mk ?? '')),
                    'nm_mk' => trim((string) ($course->nm_mk ?? '')),
                    'semester' => $course->semester ?? null,
                    'sks' => $course->sks ?? null,
                    'nilai' => $record->nilai,
                    'mutu' => $record->mutu,
                ];
            })
            ->values()
            ->all();

        return [
            'student' => [
                'mhs_id' => $student->mhs_id,
                'nim' => $student->nim,
                'nama' => trim((string) $student->nm_mhs),
                'angkatan' => $student->masuk_tahun,
                'prodi' => $student->prodi ?? $student->jurusan ?? null,
                'status' => $student->sts_mhs,
            ],
            'records' => $records,
            'source' => [
                'system' => 'simak',
                'dataset' => 'vnilaiakhir',
            ],
        ];
    }
}
