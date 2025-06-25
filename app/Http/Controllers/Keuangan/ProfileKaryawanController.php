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
                'nik' => 'string|max:20',
                'nama' => 'required|string|max:100',
                'email' => 'nullable|email|max:100',
                'nomor_telepon' => 'nullable|string|max:20',
                'alamat' => 'nullable|string|max:255',
                'jabatan' => 'required|integer',
                'fungsional' => 'nullable|integer',
                'ttm' => 'required|date',
                'pendidikan_terakhir' => 'required|string',
                'status_menikah' => 'required|boolean',
                'golongan' => 'nullable|integer',
                'status' => 'required|integer',
                'no_rekening' => 'nullable|string|max:50',
                'nama_bank' => 'nullable|string|max:100',

                'beban_max_sks' => 'nullable|numeric|min:0',
                'honor_per_ks' => 'nullable|numeric|min:0',

                'tunjangan_yayasan' => 'nullable|numeric|min:0',
                'tunjangan_fungsional' => 'nullable|numeric|min:0',
                //validasi tunangan lainnya (keluarga, makan, transport)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                
              
            ],[
                'nidn_nuptk.required' => 'NIDN/NUPTK wajib diisi.',
                'nidn_nuptk.string' => 'NIDN/NUPTK harus berupa teks.',
                'nidn_nuptk.max' => 'NIDN/NUPTK maksimal 50 karakter.',

                'nik.string' => 'NIK harus berupa teks.',
                'nik.max' => 'NIK maksimal 20 karakter.',

                'nama.required' => 'Nama wajib diisi.',
                'nama.string' => 'Nama harus berupa teks.',
                'nama.max' => 'Nama maksimal 100 karakter.',

                'email.email' => 'Format email tidak valid.',
                'email.max' => 'Email maksimal 100 karakter.',

                'nomor_telepon.max' => 'Nomor telepon maksimal 20 karakter.',

                'alamat.string' => 'Alamat harus berupa teks.',
                'alamat.max' => 'Alamat maksimal 255 karakter.',

                'jabatan.required' => 'Jabatan wajib dipilih.',
                'jabatan.integer' => 'Jabatan harus berupa angka.',

                'fungsional.integer' => 'Fungsional harus berupa angka.',

                'ttm.required' => 'Tanggal mulai tugas (TTM) wajib diisi.',
                'ttm.date' => 'Tanggal TTM tidak valid.',

                'pendidikan_terakhir.required' => 'Pendidikan terakhir wajib diisi.',
                'pendidikan_terakhir.string' => 'Pendidikan terakhir harus berupa teks.',

                'status_menikah.required' => 'Status menikah wajib diisi.',
                'status_menikah.boolean' => 'Status menikah harus berupa ya/tidak.',

                'golongan.integer' => 'Golongan harus berupa angka.',

                'status.required' => 'Status wajib diisi.',
                'status.integer' => 'Status harus berupa angka.',

                'no_rekening.string' => 'Nomor rekening harus berupa teks.',
                'no_rekening.max' => 'Nomor rekening maksimal 50 karakter.',

                'nama_bank.string' => 'Nama bank harus berupa teks.',
                'nama_bank.max' => 'Nama bank maksimal 100 karakter.',

                'beban_max_sks.numeric' => 'Beban maksimal SKS harus berupa angka.',
                'beban_max_sks.min' => 'Beban maksimal SKS minimal 0.',

                'honor_per_ks.numeric' => 'Honor per kelas harus berupa angka.',
                'honor_per_ks.min' => 'Honor per kelas minimal 0.',

                'tunjangan_yayasan.numeric' => 'Tunjangan yayasan harus berupa angka.',
                'tunjangan_yayasan.min' => 'Tunjangan yayasan minimal 0.',

                'tunjangan_fungsional.numeric' => 'Tunjangan fungsional harus berupa angka.',
                'tunjangan_fungsional.min' => 'Tunjangan fungsional minimal 0.'
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



