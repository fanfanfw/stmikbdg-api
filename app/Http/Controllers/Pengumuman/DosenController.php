<?php

namespace App\Http\Controllers\Pengumuman;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

// ? Models - views
use App\Models\TahunAjaranView;
use App\Models\KelasKuliah\KelasKuliahJoinView;
use App\Models\KampusView;
use App\Models\Perkuliahan\Pengumuman;

class DosenController extends Controller
{
    public function getAllKelas() {
        try {
            $dosen = $this->getUserAuth();
            $tahunIdArr= TahunAjaranView::orderBy('tahun_id', 'DESC')
                ->select('tahun_id')
                ->pluck('tahun_id');

            // get kelas kuliah dan cek kelas dijoin
            $kelasKuliah = KelasKuliahJoinView::where('pengajar_id', $dosen['dosen_id'])
                ->whereIn('tahun_id', $tahunIdArr)
                ->select('kelas_kuliah_id', 'tahun_id', 'kjoin_kelas', 'join_kelas_kuliah_id')
                ->get();

            $kelasKuliahIdArr = collect($kelasKuliah)->filter(function ($item) {
                return $item['kjoin_kelas'];
            })->pluck('kelas_kuliah_id');

            $filteredKelasKuliah = KelasKuliahJoinView::getKelasKuliahForPengumuman(
                $dosen['dosen_id'], $tahunIdArr, $kelasKuliahIdArr
            );

            // inisiasi
            $targets = [
                [
                    'target' => 0,
                    'ket_target' => 'All',
                ]
            ];

            foreach ($filteredKelasKuliah as $item) {
                $kampus = KampusView::where('kd_kampus', $item['kd_kampus'])
                    ->select('kd_kampus', 'lokasi')
                    ->first();
                $jnsMhs = $item['jns_mhs'] == 'R' ? 'Reguler'
                    : ($item['jns_mhs'] == 'E' ? 'Eksekutif' : 'Karyawan');

                $newItem = [
                    'target' => $item['kelas_kuliah_id'],
                    'kd_kampus' => $item['kd_kampus'],
                    'kampus' => trim($kampus['lokasi']),
                    'jns_mhs' => $jnsMhs,
                    'mk_id' => $item['matakuliah']['mk_id'],
                    'kd_mk' => trim($item['matakuliah']['kd_mk']),
                    'nm_mk' => trim($item['matakuliah']['nm_mk']),
                    'semester' => $item['matakuliah']['semester'],
                    'sks' => $item['matakuliah']['sks']
                ];

                array_push($targets, $newItem);
            }

            return $this->successfulResponseJSON([
                'targets' => $targets
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getListPengumuman(Request $request) {
        try {
            $page = $request->query('page');
            $dosen = $this->getUserAuth();

            $kelasKuliahId = $request->query('kelas_kuliah_id');

            // jika terdapat filter kelas kuliah
            if ($kelasKuliahId) {
                $listPengumuman = Pengumuman::where('target', (int) $kelasKuliahId)
                    ->where('pengirim', $dosen['dosen_id'])
                    ->orderBy('tgl_dikirim', 'DESC')
                    ->get();

                if (count($listPengumuman) > 0) {
                    return $this->successfulResponseJSON([
                        'list_pengumuman' => $listPengumuman
                    ]);
                }

                return $this->failedResponseJSON('Belum memiliki pengumuman', 404);
            }

            // get semua pengumuman
            $tahunIdArr= TahunAjaranView::orderBy('tahun_id', 'DESC')
                ->select('tahun_id')
                ->pluck('tahun_id');

            // get kelas kuliah dan cek kelas dijoin
            $kelasKuliah = KelasKuliahJoinView::with([
                'matakuliah' => function ($query) {
                    $query->select('mk_id', 'nm_mk');
                }
            ])
                ->where('pengajar_id', $dosen['dosen_id'])
                ->whereIn('tahun_id', $tahunIdArr)
                ->select('kelas_kuliah_id', 'tahun_id', 'kjoin_kelas', 'join_kelas_kuliah_id', 'mk_id')
                ->get();

            $kelasKuliahIdArr = collect($kelasKuliah)->filter(function ($item) {
                return $item['kjoin_kelas'];
            })->pluck('kelas_kuliah_id');

            $filteredKelasKuliah = KelasKuliahJoinView::getKelasKuliahForPengumuman(
                $dosen['dosen_id'], $tahunIdArr, $kelasKuliahIdArr
            )->pluck('kelas_kuliah_id')->toArray();

            array_push($filteredKelasKuliah, 0); // untuk ambil pengumuman target 0

            $listPengumuman = Pengumuman::whereIn('target', $filteredKelasKuliah)
                ->orderBy('tgl_dikirim', 'DESC')
                ->get();

            foreach ($listPengumuman as $item) {

                $matching_kelas_kuliah_id = null;
                foreach ($kelasKuliah as $kelas) {
                    if ($kelas['kelas_kuliah_id'] == $item['target']) {
                        $matching_kelas_kuliah_id = $kelas['kelas_kuliah_id'];
                        break;
                    }
                }

                if ($matching_kelas_kuliah_id) {
                    $item['keterangan_target'] = 'Pengumuman untuk kelas ' . $kelas['matakuliah']['nm_mk'];
                    $item['matakuliah'] = $kelas['matakuliah']['nm_mk'];
                }else{
                    $item['keterangan_target'] = 'Pengumuman Umum';
                    $item['matakuliah'] = null;
                }
            }

            if ($page) {
                $perPage = 5;
                $currentPage = (integer) $page ?? Paginator::resolveCurrentPage();
                $currentPageData = Collection::make($listPengumuman)->slice(($currentPage - 1) * $perPage, $perPage);
                $paginator = new Paginator($currentPageData->all(), $perPage, $currentPage);
                $paginatedData = array_values($paginator->items());
                $totalNextItems = count($listPengumuman) - ($currentPage == 1
                    ? $currentPageData->count()
                    : $currentPageData->count() + ($perPage * $currentPage)
                );

                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'list_oengumuman' => $paginatedData,
                    ],
                    'meta' => [
                        'current_page' => $currentPage,
                        'total_items' => count($listPengumuman),
                        'items_per_page' => $paginator->perPage(),
                        'prev_page_url' => $currentPage == 1 ? null
                            :  config('app.url') . 'api/pengumuman/dosen/list' . substr($paginator->previousPageUrl(), 1),
                        'next_page_url' => ($totalNextItems > -1 and count($listPengumuman) > $perPage)
                            ? config('app.url') . 'api/pengumuman/dosen/list?page=' . $currentPage + 1
                            : null,
                    ],
                ], 200);
            }

            return $this->successfulResponseJSON([
                'list_pengumuman' => $listPengumuman
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
