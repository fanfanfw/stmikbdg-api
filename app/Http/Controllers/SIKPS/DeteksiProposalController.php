<?php

namespace App\Http\Controllers\SIKPS;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// ? Models - Table
use App\Models\SIKPS\Fingerprints;
use App\Models\SIKPS\Similarities;
use App\Models\SIKPS\SimilarityLimit;

/**
 * Semua method dalam class ini digunakan untuk admin
 */
class DeteksiProposalController extends Controller
{
    public function addFingerprints(Request $request) {
        try {
            $request->validate([
                'fingerprints' => 'required|array'
            ]);

            DB::beginTransaction();

            $insert = Fingerprints::insert($request->fingerprints);

            if ($insert) {
                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Fingerprints berhasil dibuat'
                ], 201);
            }

            DB::rollBack();

            return response()->json([
                'status' => 'fail',
                'message' => 'Fingerprints gagal dibuat. Unknown error'
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();

            return ErrorHandler::handle($e);
        }
    }

    public function getAllFingerprints() {
        try {
            $fingerprints = Fingerprints::select('fingerprint_id', 'judul', 'file_dokumen')
                ->orderBy('fingerprint_id', 'DESC')
                ->get();

            return $this->successfulResponseJSON([
                'fingerprints' => $fingerprints
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function deleteFingerprint(Request $request) {
        try {
            $fingerprintId = $request->fingerprint_id;

            if ($fingerprintId) {
                $fingerprint = Fingerprints::where('fingerprint_id', (int) $fingerprintId)->first();

                if ($fingerprint) {
                    DB::beginTransaction();
                    $delete = Fingerprints::where('fingerprint_id', (int) $fingerprintId)->delete();

                    if ($delete) {
                        DB::commit();

                        return response()->json([
                            'status' => 'success',
                            'message' => 'Fingerprint proposal berhasil dihapus'
                        ], 200);
                    }
                }
            }

            return response()->json([
                'status' => 'fail',
                'message' => 'Fingerprint tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function updateFingerprint(Request $request) {
        try {
            $fingerprintId = $request->fingerprint_id;

            if ($fingerprintId) {
                $fingerprint = Fingerprints::where('fingerprint_id', (int) $fingerprintId)->first();

                if ($fingerprint) {
                    if ($request->file_dokumen) {
                        $request->validate([
                            'judul' => 'required|string',
                            'file_dokumen' => 'required|string',
                            'n_gram' => 'required|string',
                            'hashing' => 'required|string',
                            'winnowing' => 'required|string',
                            'fingerprint' => 'required|string',
                            'total_fingerprint' => 'required',
                            'total_ngram' => 'required',
                            'total_hash' => 'required',
                            'total_window' => 'required',
                            'is_generated' => 'required',
                        ]);

                        $data = [
                            'judul' => $request->judul,
                            'file_dokumen' => $request->file_dokumen,
                            'n_gram' => $request->n_gram,
                            'hashing' => $request->hashing,
                            'winnowing' => $request->winnowing,
                            'fingerprint' => $request->fingerprint,
                            'total_fingerprint' => $request->total_fingerprint,
                            'total_ngram' => $request->total_ngram,
                            'total_hash' => $request->total_hash,
                            'total_window' => $request->total_window,
                            'is_generated' => $request->is_generated,
                            'created_at' => now(),
                        ];
                    } else {
                        $request->validate([
                            'judul' => 'required|string',
                        ]);

                        $data = [
                            'judul' => $request->judul,
                        ];
                    }

                    DB::beginTransaction();

                    $update = Fingerprints::where('fingerprint_id', $fingerprintId)->update($data);

                    if ($update) {
                        DB::commit();

                        return response()->json([
                            'status' => 'success',
                            'message' => 'Fingerprint berhasil diperbarui'
                        ], 200);
                    }

                    DB::rollBack();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Fingerprint gagal diperbarui'
                    ], 500);
                }
            }

            return response()->json([
                'status' => 'fail',
                'message' => 'Fingerprint tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function getAllRiwayatDeteksi() {
        try {
            $listHasilDeteksi = Similarities::orderBy('similarity_id', 'DESC')->get();

            return $this->successfulResponseJSON([
                'hasil_deteksi' => $listHasilDeteksi
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function deleteAllGeneratedFingerprints() {
        try{
            DB::beginTransaction();

            $delete = Fingerprints::where('is_generated', true)->delete();

            if ($delete) {
                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Generated fingerprints berhasil dihapus'
                ], 200);
            }

            DB::rollBack();

            return response()->json([
                'status' => 'fail',
                'message' => 'Generated fingerprints gagal dihapus'
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function getDetail(Request $request) {
        try {
            $fingerprintId = $request->fingerprint_id;

            if ($fingerprintId) {
                $fingerprint = Fingerprints::where('fingerprint_id', (int) $fingerprintId)
                    ->select('fingerprint_id', 'judul', 'file_dokumen', 'is_generated')
                    ->first();

                if ($fingerprint) {
                    return $this->successfulResponseJSON([
                        'fingerprint' => $fingerprint,
                    ]);
                }
            }

            return response()->json([
                'status' => 'fail',
                'message' => 'Proposal tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function updateSimilarityLimit(Request $request) {
        try {
            $request->validate([
                'max_limit' => 'required|integer'
            ]);

            DB::beginTransaction();
            SimilarityLimit::truncate();
            $insert = SimilarityLimit::insert($request->all());

            if ($insert) {
                DB::commit();
                return $this->successfulResponseJSONV2('Batas kemiripan berhasil diperbarui');
            }

            DB::rollBack();
            return $this->failedResponseJSON('Batas kemiripan gagal diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function getSimilarityLimit() {
        try {
            $similarityLimit = SimilarityLimit::first();
            return $this->successfulResponseJSON([
                'similarity_limit' => $similarityLimit
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
