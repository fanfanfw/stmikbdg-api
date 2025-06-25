<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Imports\Keuangan\MasterBeasiswaImportCollection;
use App\Models\Keuangan\MasterBeasiswa;
use App\Models\Keuangan\PenerimaBeasiswa;
use App\Models\Keuangan\TahunAkademik;
use App\Models\TahunAjaranView;
use App\Models\Users\Mahasiswa;
use App\Models\Users\MahasiswaView;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MasterBeasiswaController extends Controller
{
    protected $master_beasiswa_model;
    protected $penerima_beasiswa_model;
    protected $tahun_akademik_model;
    protected $mahasiswa_model;
    protected $master_beasiswa_import_collection;
    protected $storage_path;

    public function __construct() {
        $this->master_beasiswa_model = new MasterBeasiswa();
        $this->penerima_beasiswa_model = new PenerimaBeasiswa();
        $this->tahun_akademik_model = new TahunAkademik();
        $this->mahasiswa_model = new Mahasiswa();
        $this->master_beasiswa_import_collection = new MasterBeasiswaImportCollection();
        $this->storage_path = 'keuangan/beasiswa/sk';
    }

    public function getAll_filters_tahun_ajaran (Request $request) {
        $data = TahunAjaranView::getTahunAjaranWithKRS()->filter(function ($item) {
            return $item['krs']->count() > 0;
        })
        ->map(function ($item) {
            return [
                'tahun_id' => $item['tahun_id'],
                'uraian' => $item['uraian']
            ];
        })
        ->sortByDesc('tahun_id')
        ->values();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getAll(Request $request) {
        $filters = $this->parseFilters($request->query('filters') ?? []);
        if(!isset($filters['by']) || !isset($filters['tahun_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'filters `by` harus diisi, [beasiswa/mahasiswa]. filters `tahun_id` harus diisi, angka'
            ], 403);
        }

        if(!in_array($filters['by'], ['beasiswa', 'mahasiswa']) || !is_numeric($filters['tahun_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'filters `by` harus diisi, [beasiswa|mahasiswa]. filters `tahun_id` harus diisi, angka'
            ], 403);
        }

        if($filters['by'] == 'mahasiswa') {
            $beasiswa = $this->penerima_beasiswa_model
                ->with('master_beasiswa')
                ->get();

            $mahasiswa = $this->mahasiswa_model
                ->where('sts_mhs', 'A')
                ->where('kd_kampus', 'A')
                ->whereNotNull('krs_id_last')
                ->whereIn('mhs_id', $beasiswa->pluck('mhs_id')->toArray())
                ->whereHas('krs.krsMatkul.kelasKuliahJoin', function ($query) use ($filters) {
                    $query->where('tahun_id', $filters['tahun_id']);
                })
                ->with('jurusan')
                // ->pluck(['mhs_id', 'nm_mhs', 'nim', 'jurusan'])
                ->get();

            $data = $beasiswa->map(function ($item) use ($mahasiswa) {
                $item['mahasiswa'] = $mahasiswa->where('mhs_id', $item['mhs_id'])->select(['nm_mhs', 'nim', 'jurusan'])->first();
                return $item;
            })->filter(function ($item) {
                return $item['mahasiswa'] != null;
            })->values();
        }else if($filters['by'] == 'beasiswa') {

            $mahasiswa = $this->mahasiswa_model
                ->where('sts_mhs', 'A')
                ->where('kd_kampus', 'A')
                ->whereNotNull('krs_id_last')
                ->with('jurusan', 'krs')
                ->get();
             
            $beasiswa = $this->master_beasiswa_model
                ->with('penerima_beasiswa', function ($query) use ($mahasiswa) {
                    $query->map(function($item) use ($mahasiswa) {
                        $item['mahasiswa'] = $mahasiswa->where('mhs_id', $item['mhs_id'])->select(['nm_mhs', 'nim', 'jurusan'])->first();
                        return $item;
                    });
                })
                ->get();

            $data = $beasiswa;
        }

        return response()->json([
            'success' => true,
            'data' => $data ?? []
        ]);
    }

    public function create(Request $request) {
        try {

            $skipped = [];

            $request->validate([
                'nama_beasiswa' => 'required|string|max:255',
                'id_thn_akademik' => 'required|integer',
                'file_sk' => 'required|file|mimes:pdf,doc,docx|max:2048',
                // 'semester' => 'required',
                // 'berlaku_mulai' => 'required|date',
                // 'berlaku_sampai' => 'required|date',
                'file_excel' => 'nullable|file|mimes:xlsx,xls',
                'mhs_id' => 'nullable',
            ]);

            // $tahun_akademik = $this->tahun_akademik_model
            //     ->where('status', 1)
            //     ->where('id_thn_akademik', $request->id_thn_akademik)
            //     ->select(['thn_akademik', 'id_thn_akademik'])
            //     ->first();

            $tahun_akademik = TahunAjaranView::getTahunAjaranWithKRS()->filter(function ($item) {
                    return $item['krs']->count() > 0;
                })->map(function ($item) {
                    return [
                        'tahun_id' => $item['tahun_id'],
                        'uraian' => $item['uraian']
                    ];
                })
                ->sortByDesc('tahun_id')
                ->values()
                ->where('tahun_id', $request->id_thn_akademik)
                ->first();

            // return response()->json([
            //     'success' => false,
            //     'data' => $tahun_akademik['uraian']
            // ]);

            if(!$tahun_akademik) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tahun akademik tidak ditemukan.',
                ], 404);
            }

            if($request->hasFile('file_sk')) {
                $fileName = 'SK_BEASISWA_'.str_replace(' / ', '_', $tahun_akademik['uraian']).'.pdf';

                $response_file = $this->uploadFile('keuangan/beasiswa/sk', $fileName, $request->file('file_sk'));

                if(!$response_file['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $response_file['message'],
                        'error' => $response_file['error']
                    ]);
                }

                $url = $response_file['data']['url'];
            }

            if(!$request->hasFile('file_excel')) { // Input Manual
                if(!$request->filled('mhs_id')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda belum memilih mahasiswa.'
                    ], 400);
                }

                $hasActive = $this->penerima_beasiswa_model
                    ->where('mhs_id', $request->mhs_id)
                    ->whereHas('master_beasiswa', function ($query) use ($request, $tahun_akademik) {
                        $query->where('id_thn_akademik', $tahun_akademik['tahun_id'])
                            // ->where('semester', $request->semester)
                            ->where('status', 1);
                    })
                    ->where('status', 1)
                    ->exists();
                
                if($hasActive) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mahasiswa ini sudah memiliki beasiswa aktif di tahun akademik dan semester ini.'
                    ], 400);
                }
            }else{ // Input Excel

                $mahasiswa = $this->mahasiswa_model
                    ->where('sts_mhs', 'A')
                    ->where('kd_kampus', 'A')
                    ->whereNotNull('krs_id_last')
                    ->with('jurusan')
                    ->get();

                $master_beasiswa_import_collection = $this->master_beasiswa_import_collection;

                Excel::import($master_beasiswa_import_collection, $request->file('file_excel'));

                $data_excel_nims = $master_beasiswa_import_collection->rows->map(function ($row) {
                    return (string) $row['nim'];
                });

                if(empty($data_excel_nims)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda belum memilih mahasiswa ke dalam file excel tersebut.'
                    ], 400);
                }

                $mhs_exists = $mahasiswa->whereIn('nim', $data_excel_nims->toArray())->values()->pluck('mhs_id');

                if(empty($mhs_exists)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mahasiswa tersebut tidak ditemukan.'
                    ], 400);
                }

                $valid = 0;

                foreach ($mhs_exists as $mhs_id) {
                    $hasActive = $this->penerima_beasiswa_model
                        ->where('mhs_id', $mhs_id)
                        ->whereHas('master_beasiswa', function ($query) use ($request, $tahun_akademik) {
                            $query
                                ->where('id_thn_akademik', $tahun_akademik['tahun_id'])
                                // ->where('semester', $request->semester)
                                ->where('status', 1);
                        })
                        ->where('status', 1)
                        ->exists();
                    
                    if(!$hasActive) $valid++;
                }

                if($valid == 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Semua mahasiswa di file sudah memiliki beasiswa aktif di tahun akademik dan semester ini.'
                    ]);
                }
            }

            $beasiswa = $this->master_beasiswa_model->create([
                'nama_beasiswa' => $request->nama_beasiswa,
                'id_thn_akademik' => $tahun_akademik['tahun_id'],
                'file_sk' => $url ?? null,
                // 'semester' => $request->semester,
                // 'berlaku_mulai' => $request->berlaku_mulai,
                // 'berlaku_sampai' => $request->berlaku_sampai,
                'status' => 1,
            ]);

            if(!$request->hasFile('file_excel')) { // Input Manual
                if(!$request->filled('mhs_id')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda belum memilih mahasiswa.'
                    ], 400);
                }

                $beasiswa->penerima_beasiswa()->create([
                    'mhs_id' => $request->mhs_id,
                    'status' => 1
                ]);
            } else { // Input Excel

                $payload = $mhs_exists->map(function ($mhs_id) use ($beasiswa) {
                        return [
                            'mhs_id' => $mhs_id,
                            'status' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'id_beasiswa' => $beasiswa->id_beasiswa
                        ];
                    });
                

                $beasiswa->penerima_beasiswa()->insert($payload->toArray());
            }

            return response()->json([
                'success' => true,
                'data' => $beasiswa
            ]);
        } catch (\Exception $error) {
            return response()->json([
                'success' => false,
                'message' => $error->getMessage(),
                'error' => $error
            ]);
        }
    }

    public function update_by_id(Request $request, int $id_beasiswa) {
        try {

            $skipped = [];

            $beasiswa = $this->master_beasiswa_model->with('penerima_beasiswa')->find($id_beasiswa);

            if (!$beasiswa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data beasiswa tidak ditemukan'
                ], 404);
            }

            // dd($request->all());
            
            $request->validate([
                'nama_beasiswa' => 'required|string',
                'id_thn_akademik' => 'required|integer',
                'semester' => 'required|integer',
                'file_sk' => 'nullable|file|mimes:pdf|max:2048',
                'file_excel' => 'nullable|file|mimes:xlsx,xls',
            ]);


            // $tahun_akademik = $this->tahun_akademik_model
            //     ->where('status', 1)
            //     ->where('id_thn_akademik', $request->id_thn_akademik)
            //     ->select(['thn_akademik', 'id_thn_akademik'])
            //     ->first();

            $tahun_akademik = TahunAjaranView::getTahunAjaranWithKRS()->filter(function ($item) {
                    return $item['krs']->count() > 0;
                })->map(function ($item) {
                    return [
                        'tahun_id' => $item['tahun_id'],
                        'uraian' => $item['uraian']
                    ];
                })
                ->sortByDesc('tahun_id')
                ->values()
                ->where('tahun_id', $request->id_thn_akademik)
                ->first();

            if(!$tahun_akademik) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tahun akademik tidak ditemukan.',
                ], 404);
            }

            $beasiswa_payload = [
                'nama_beasiswa' => $request->nama_beasiswa,
                'id_thn_akademik' => $tahun_akademik->tahun_id,
                'semester' => $request->semester,
            ];

            if($request->hasFile('file_sk')) {
                $file = $request->file('file_sk');

                $fileName = 'SK_BEASISWA_'.str_replace(' ', '_', $tahun_akademik->uraian).'_'.$request->semester.'.pdf';

                $response_file = $this->uploadFile($this->storage_path, $fileName, $file);

                if(!$response_file['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $response_file['message'],
                        'error' => $response_file['error']
                    ]);
                }

                $beasiswa_payload['file_sk'] = $response_file['data']['url'];
            }

            $beasiswa->update($beasiswa_payload);

            $status = 1;

            if($request->hasFile('file_excel')) {
                $this->penerima_beasiswa_model->where('id_beasiswa', $id_beasiswa)->update([
                    'status' => 0
                ]);

                $mahasiswa = $this->mahasiswa_model
                    ->where('sts_mhs', 'A')
                    ->where('kd_kampus', 'A')
                    ->whereNotNull('krs_id_last')
                    ->with('jurusan')
                    ->get();

                Excel::import($this->master_beasiswa_import_collection, $request->file('file_excel'));

                $data_excel_nims = $this->master_beasiswa_import_collection->rows->map(function ($row) {
                    return (string) $row['nim'];
                });

                if(empty($data_excel_nims)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda belum memilih mahasiswa ke dalam file excel tersebut.'
                    ], 400);
                }

                $mhs_exists = $mahasiswa->whereIn('nim', $data_excel_nims->toArray())->values()->pluck('mhs_id');

                if(empty($mhs_exists)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mahasiswa tersebut tidak ditemukan.'
                    ], 400);
                }


                foreach ($mhs_exists as $mhs_id) {
                    $hasActiveInSamePeriod = $this->penerima_beasiswa_model
                        ->where('mhs_id', $mhs_id)
                        ->where('status', 1)
                        ->where('id_beasiswa', '!=', $id_beasiswa)
                        ->whereHas('master_beasiswa', function ($query) use ($request) {
                            $query->where('id_thn_akademik', $request->id_thn_akademik);
                                // ->where('semester', $request->semester);
                        })
                        ->exists();
                    
                    if ($hasActiveInSamePeriod) {
                        $skipped[] = $mhs_id;
                        continue;
                    }

                    $wasInactiveInSamePeriod = $this->penerima_beasiswa_model
                        ->where('mhs_id', $mhs_id)
                        ->where('status', 0)
                        ->whereHas('master_beasiswa', function ($query) use ($request) {
                            $query->where('id_thn_akademik', $request->id_thn_akademik);
                                // ->where('semester', $request->semester);
                        })
                        ->exists();

                    // Jika tidak pernah aktif maupun nonaktif di periode yang sama, atau dulunya nonaktif dan sekarang aktif, lanjutkan
                    if (!$hasActiveInSamePeriod || $wasInactiveInSamePeriod) {
                        $this->penerima_beasiswa_model->updateOrCreate(
                            [
                                'id_beasiswa' => $id_beasiswa,
                                'mhs_id' => $mhs_id,
                            ],
                            [
                                'status' => $status,
                            ]
                        );
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Beasiswa berhasil diperbarui.',
                'data' => $beasiswa,
                'skipped' => isset($mahasiswa) ? $mahasiswa->whereIn('mhs_id', $skipped)->values()->pluck('nim') : []
            ]);

        } catch (\Exception $error) {
            return response()->json([
                'success' => false,
                'message' => $error->getMessage(),
                'error' => $error
            ]);
        }
    }

    public function delete_by_id(Request $request, int $id_beasiswa) {
        try {
            $data = $this->master_beasiswa_model->find($id_beasiswa);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $data->update([
                'status' => 0
            ]);

            $data->penerima_beasiswa()->update([
                'status' => 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Beasiswa dan data penerima berhasil dihapus!',
                'data' => $data
            ], 200);
        } catch (\Exception $error) {
            return response()->json([
                'success' => false,
                'message' => $error->getMessage(),
                'error' => $error
            ]);
        }
    }

    public function delete_by_id_penerima(Request $request, int $id_penerima) {
        try {
            $data = $this->penerima_beasiswa_model->find($id_penerima);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $data->update([
                'status' => 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data penerima berhasil dihapus!',
                'data' => $data
            ], 200);
        } catch (\Exception $error) {
            return response()->json([
                'success' => false,
                'message' => $error->getMessage(),
                'error' => $error
            ]);
        }
    }

    public function delete_by_mhs_id(Request $request, int $mhs_id) {
        try {
            $exist = $this->penerima_beasiswa_model->where('mhs_id', $mhs_id)->get();

            if ($exist->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data penerima beasiswa tidak ditemukan'
                ], 404);
            }

            $data = $this->penerima_beasiswa_model->where('mhs_id', $mhs_id)->update([
                'status' => 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'data penerima berhasil dihapus!'
            ], 200);
        } catch (\Exception $error) {
            return response()->json([
                'success' => false,
                'message' => $error->getMessage(),
                'error' => $error
            ]);
        }
    }
}
