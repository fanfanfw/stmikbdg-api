<?php

namespace App\Http\Controllers\Pengumuman;

use App\Http\Controllers\Controller;
use App\Models\Perkuliahan\Pengumuman;
use Illuminate\Http\Request;

use App\Exceptions\ErrorHandler;
use App\Models\KelasKuliah\KelasKuliahJoinView;
use App\Models\KelasKuliah\KelasKuliahView;
use App\Models\KRS\KRSMatkul;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class PublicController extends Controller
{
    public function getListPengumuman(Request $request) {
        try {
            $page = $request->query('page');
            $listPengumuman = Pengumuman::orderBy('tgl_dikirim', 'DESC')
                ->where('target', 0)
                ->distinct('tgl_dikirim')
                ->get();


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
                        'list_pengumuman' => $paginatedData,
                    ],
                    'meta' => [
                        'current_page' => $currentPage,
                        'total_items' => count($listPengumuman),
                        'items_per_page' => $paginator->perPage(),
                        'prev_page_url' => $currentPage == 1 ? null
                            :  config('app.url') . 'api/pengumuman/admin/list' . substr($paginator->previousPageUrl(), 1),
                        'next_page_url' => ($totalNextItems > -1 and count($listPengumuman) > $perPage)
                            ? config('app.url') . 'api/pengumuman/admin/list?page=' . $currentPage + 1
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
