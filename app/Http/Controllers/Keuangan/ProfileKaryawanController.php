<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\ProfileKaryawan;
use Illuminate\Http\Request;

class ProfileKaryawanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        $data = ProfileKaryawan::all();
        // $data = ProfileKaryawan::SimpleProfileKaryawan()->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);

    }

    public function allDataKaryawan()
    {
        $data = ProfileKaryawan::AllDataKaryawan()->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function dataKaryawanBulanIni()
    {

        $bulan = $bulan ?? now()->month;
        $tahun = $tahun ?? now()->year;

        $data = ProfileKaryawan::DataKaryawanWithFilter($bulan, $tahun)->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

   

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

     public function dataKaryawanBulanIniById($id)
    {

        $bulan = $bulan ?? now()->month;
        $tahun = $tahun ?? now()->year;

        $data = ProfileKaryawan::DataKaryawanWithFilter($bulan, $tahun)->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);
    }

     /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{

            $validated = $request->validate([
                'nidn_nuptk' => 'required|string|max:50',
                'nik' => 'string|max:50',
                'nama' => 'required|string|max:50',
                'email' => 'required|string|max:75',
                'no_telepon' => 'required|string|max:50',
                'alamat' => 'required|string',
                'pendidikan_terakhir' => 'required|string',                                                                                                                                                                                                                                             
                'tmt' => 'required|date',                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               
                'no_rekening' => 'string',
                'nama_bank' => 'string',
                'status_menikah' => 'required|boolean',
                'jabatan' => 'integer',
                'golongan' => 'integer',
                'fungsional' => 'integer',
                'status' => 'integer',
                'beban_max_sks' => 'integer',                                                                                                                                                                                                                                                                                                                                                                                                                                                                   
                'honor_per_sks' => 'integer',
                'tunjangan_yayasan' => 'integer',
                'tunjangan_fungsional' => 'integer',
                //validasi tunangan lainnya (keluarga, makan, transport)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                
              
            ],[
                'nidn_nuptk.required' => 'NIDN/NUPTK wajib diisi.',
                'nidn_nuptk.string'   => 'NIDN/NUPTK harus berupa teks.',
                'nidn_nuptk.max'      => 'NIDN/NUPTK maksimal 50 karakter.',

                'nik.string' => 'Kolom nik harus berupa teks.',
                'nik.max'    => 'Kolom nik maksimal 50 karakter.',

                'nama.required' => 'Kolom nama wajib diisi.',
                'nama.string'   => 'Kolom nama harus berupa teks.',
                'nama.max'      => 'Kolom D2 maksimal 50 karakter.',

                'email.required' => 'Kolom email wajib diisi.',
                'email.string'   => 'Kolom email harus berupa teks.',
                'email.max'      => 'Kolom email maksimal 75 karakter.',

                'no_telpon.required' => 'Kolom no telpon wajib diisi.',
                'no_telpon.string'   => 'Kolom no telpon harus berupa teks.',
                'no_telpon.max'      => 'Kolom no telpon maksimal 50 karakter.',

                'alamat.required' => 'Kolom alamat wajib diisi.',
                'alamat.string'   => 'Kolom alamat harus berupa teks.',

                'pendidikan_terakhir.required' => 'Kolom pendidikan_terakhir wajib diisi.',
                'pendidikan_terakhir.string'   => 'Kolom pendidikan_terakhir harus berupa teks.',

                'tmt.required' => 'Kolom tmt wajib diisi.',
                'tmt.date'     => 'Kolom tmt harus berupa tanggal yang valid.',

                'no_rekening.string' => 'Kolom no rekening harus berupa teks.',
                'nama_bank.string' => 'Kolom nama bank harus berupa teks.',

                'status_menikah.required' => 'Kolom status menikah wajib diisi.',
                'status_menikah.boolean'  => 'Kolom status menikah harus berupa true atau false.',
            ]);

            $data = ProfileKaryawan::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil ditambahkan.',
                'data' => $data
            ],201);

        }catch (\Illuminate\Validation\ValidationException $error){
            return response()->json([
                'success' => false,
                'message' =>  'Terdapat kesalahan pada input Anda. Mohon periksa atau lengkapi kembali',
                'errors' => $error->errors(),
            ], 422);
        } catch (\Exception $error) {
           
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data.',
                'error' => $error->getMessage() // hapus di production
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}



