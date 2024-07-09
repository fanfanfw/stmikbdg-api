<?php

namespace App\Http\Controllers\Pengumuman;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exceptions\ErrorHandler;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// ? Firebase Cloud Messaging
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

// ? Models - Views
use App\Models\KelasKuliah\KelasKuliahJoinView;
use App\Models\KRS\MatkulDiselenggarakanView;

// ? Models - Table
use App\Models\KRS\KRSMatkul;
use App\Models\KRS\KRS;
use App\Models\Perkuliahan\FCMClients;
use App\Models\Perkuliahan\Pengumuman;

class MainController extends Controller
{
    public function addPengumuman(Request $request) {
        try {
            $user = $this->getUserAuth();

            if (auth()->user()->is_mhs) {
                return $this->failedResponseJSON('Enpoint ini hanya tersedia untuk Admin Perkuliahan dan Dosen', 403);
            }

            $request->validate([
                'target' => 'required|integer',
                'image' => 'nullable|string',
                'message' => 'required|string',
            ]);

            // ? user adalah admin atau target pengumuman untuk semua
            if (auth()->user()->is_admin or ($request->target === 0)) {
                if ($request->target !== 0) {
                    return $this->successfulResponseJSONV2('Admin hanya bisa mengirim pengumuman untuk semua', 403);
                }

                $pengumuman = $request->all();
                $pengumuman['tgl_dikirim'] = Carbon::now();
                $pengumuman['pengirim'] = auth()->user()->is_admin ? $user['admin_id']
                    : $user['dosen_id'];
                $pengumuman['nm_pengirim'] = isset($user['gelar']) ?
                    $user['nama'] . ', ' . $user['gelar'] : $user['nama'];
                $pengumuman['target'] = 0;
                $pengumuman['avatar_pengirim'] = auth()->user()->image;

                if ($request->image and !is_null($request->image)) {
                    $pengumuman['image'] = config('app.url')
                        . 'storage/pengumuman/images/' . $pengumuman['image'];
                }

                DB::beginTransaction();

                $insert = Pengumuman::insert($pengumuman);

                if ($insert) {
                    DB::commit();

                    /**
                     * kirim notif lewat fcm
                     */
                    self::sendNotif(null, $pengumuman);

                    return $this->successfulResponseJSONV2('Pengumuman berhasil dikirim untuk semua', 200);
                }

                DB::rollBack();

                return $this->failedResponseJSON('Pengiriman gagal dikirim', 500);
            }

            // ? user adalah dosen
            if (auth()->user()->is_dosen) {
                /**
                 * pastikan bahwa target atau kelas kuliah
                 * yang dipilih adalah milik dosennya sendiri
                 **/
                $punyaDosen = KelasKuliahJoinView::where('kelas_kuliah_id', $request->target)
                    ->where('pengajar_id', $user['dosen_id'])
                    ->select('kelas_kuliah_id')
                    ->first();

                if ($punyaDosen) {
                    $kelasKuliahIdArr = null;

                    // cek kelas dijoin
                    $joinKelasKuliahIdArr = KelasKuliahJoinView::where('join_kelas_kuliah_id', $request->target)
                        ->select('kelas_kuliah_id', 'join_kelas_kuliah_id')
                        ->pluck('kelas_kuliah_id')
                        ->toArray();

                    if (count($joinKelasKuliahIdArr) > 0) {
                        $kelasKuliahIdArr = array_merge([$request->target], $joinKelasKuliahIdArr);
                    }

                    // cek mahasiswa yang mengambil kelas kuliah
                    $krsIdArr = KRSMatkul::whereIn('kelas_kuliah_id', $kelasKuliahIdArr)
                        ->select('krs_mk_id', 'krs_id')
                        ->pluck('krs_id');

                    // simpan pengumuman ke db untuk setiap kelas
                    $pengumumanArr = [];
                    $tglKirim = Carbon::now();

                    DB::beginTransaction();

                    foreach ($kelasKuliahIdArr as $item) {
                        if ($request->image and !is_null($request->image)) {
                            $image = config('app.url') . 'storage/pengumuman/images/' . $request->image;
                        }

                        $pengumuman = [
                            'target' => $item,
                            'pengirim' => $user['dosen_id'],
                            'nm_pengirim' => $user['nama'] . ', ' . $user['gelar'],
                            'image' => is_null($request->image) ? null : $image,
                            'tgl_dikirim' => $tglKirim,
                            'message' => $request->message,
                            'avatar_pengirim' => auth()->user()->image
                        ];

                        array_push($pengumumanArr, $pengumuman);
                    }

                    $insert = Pengumuman::insert($pengumumanArr);

                    if ($insert) {
                        DB::commit();

                        $mhsIdArr = KRS::whereIn('krs_id', $krsIdArr)
                            ->select('krs_id', 'mhs_id')
                            ->pluck('mhs_id');

                        /**
                         * kirim notif lewat fcm
                         */
                        self::sendNotif($mhsIdArr, $pengumuman);

                        return $this->successfulResponseJSONV2('Pengumuman berhasil dikirim', 200);
                    }

                    DB::rollBack();

                    return $this->failedResponseJSON('Pengumuman gagal dikirim', 500);
                }
            }

            return $this->failedResponseJSON('Target pengumuman tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    private function sendNotif($mhsIdArr = null, $pengumuman) {
        try {
            $body = $pengumuman['message'];

            // buat judul untuk notifikasi
            if ($pengumuman['target'] === 0) {
                // pengumuman dari admin
                $title = 'Pengumuman untuk Seluruh Civitas';
            } else {
                // pengumuman mata kuliah
                $kelas = KelasKuliahJoinView::where('kelas_kuliah_id', $pengumuman['target'])
                    ->select('kelas_kuliah_id', 'mk_id')
                    ->first();

                if (!$kelas or !array_key_exists('mk_id', $kelas->toArray())) {
                    $kelas = KelasKuliahJoinView::where('join_kelas_kuliah_id', $pengumuman['target'])
                        ->select('kelas_kuliah_id', 'mk_id')
                        ->first();
                }

                $matkul = MatkulDiselenggarakanView::where('mk_id', $kelas['mk_id'])
                    ->select('mk_id', 'nm_mk')
                    ->first();

                $title = 'Pengumuman Mata Kuliah ' . trim($matkul['nm_mk']);
            }

            // jika null, maka pengumuman dari admin dan ditujukan untuk semua mahasiswa
            if (is_null($mhsIdArr)) {
                $tokens = FCMClients::where('sts_mhs', 'A')
                    ->select('fcm_client_id', 'client_token')
                    ->pluck('client_token')
                    ->toArray();
            } else {
                $tokens = FCMClients::whereIn('mhs_id', $mhsIdArr)
                    ->select('fcm_client_id', 'client_token')
                    ->pluck('client_token')
                    ->toArray();
            }

            // send notifikasi
            $messaging = app('firebase.messaging');

            // kirim secara bertahap (chunk)
            $tokensCollection = collect($tokens);

            $tokensCollection->chunk(50)->each(function ($chunk) use ($messaging, $title, $body) {
                foreach ($chunk as $token) {
                    $message = CloudMessage::withTarget('token', $token)
                        ->withNotification(Notification::create($title, $body));

                    $messaging->send($message);
                }
            });
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
