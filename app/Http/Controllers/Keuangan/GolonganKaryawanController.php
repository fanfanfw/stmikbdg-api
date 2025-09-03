<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\GolonganKaryawan;
use Illuminate\Http\Request;

class GolonganKaryawanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = GolonganKaryawan::all();

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
        //protected $fillable = ['nama', 'pendidikan_terakhir', 'honor'];

        try{

            $validated = $request->validate([
                'nama' => 'required|string|max:255',
                'pendidikan_terakhir' => 'required|string',
                'honor' => 'required|numeric',
            ],[
                'nama.required' => 'Kolom wajib diisi',
                'nama.string' => 'Masukan nama dengan benar',
                'pendidikan_terakhir.string' => 'Masukan pendidikan terkahir dengan benar',
                'honor.required' => 'Kolom wajib diisi',
                'honor.numeric' => 'Kolom wajib disi dengan angka',
            ]);

            $data = GolonganKaryawan::create($validated);

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
     * Display the specified resource.
     */
    public function show($id)
    {
        try{

            $data = GolonganKaryawan::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);

        }catch  (\Illuminate\Database\Eloquent\ModelNotFoundException $error) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
                'errors' => $error->getMessage()
            ], 404);
 
 
        } catch (\Exception $error) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menampilkan data.',
                'error' => $error->getMessage() // hapus di production
 
                
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try{

            $validated = $request->validate([
                'nama' => 'required|string|max:255',
                'pendidikan_terakhir' => 'string',
                'honor' => 'required|numeric',
            ],[
                'nama.required' => 'Kolom wajib diisi',
                'nama.string' => 'Masukan nama dengan benar',
                'pendidikan_terakhir.string' => 'Masukan pendidikan terkahir dengan benar',
                'honor.required' => 'Kolom wajib diisi',
                'honor.numeric' => 'Kolom wajib disi dengan angka',
            ]);

            $data = GolonganKaryawan::findOrFail($id);

            $filtered = collect($validated)->only((new GolonganKaryawan)->getFillable())->toArray();

            $data->update($filtered);

            return response()->json([
                'succsess' => true,
                'message' => 'Data golongan berhasil diupdate',
                'data' => $filtered
            ],200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $error){
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 422);


        } catch (\Illuminate\Validation\ValidationException $error) {
        
            return response()->json([
                'success' => false,
                'message' => 'Terdapat kesalahan pada input Anda. Mohon periksa atau lengkapi kembali',
                'errors' => $error->errors()
            ], 422);


        
        }  catch (\Exception $error) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat update data.',
                'error' => $error->getMessage() // hapus di production

            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        
        try{

            $data = GolonganKaryawan::findOrFail($id);
            $data->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus',
                'data' => $data->only(['nama','pendidikan_terakhir'])
            ], 200);

        }catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Data gagal dihapus karena golongan masih digunakan. Periksa data karyawan atau data lain yang masih mneggunakan golongan yang dimaksud.',
                'error' => $e->getMessage()
            ], 409);       
        } catch (\Exception $error) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data.',
                'error' => $error->getMessage() // hapus di production

            ]);
        }
    }


    public function allDataPendidikanTerakhir(){
        
         try{
            $data = GolonganKaryawan::AllDataPendidikanTerakhir();

            return response()->json([
                'success' => true,
                'data' => $data,
        ], 200);

        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Data gagal dihapus karena golongan masih digunakan. Periksa data karyawan atau data lain yang masih mneggunakan golongan yang dimaksud.',
                'error' => $e->getMessage()
            ], 409);       
        } catch (\Exception $error) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data.',
                'error' => $error->getMessage() // hapus di production

            ]);
        }
    }
}
