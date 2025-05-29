<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\StatusKaryawan;
use Illuminate\Http\Request;

class StatusKaryawanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = StatusKaryawan::all();

        return response()->json([
            'success' => true,
            'data'=> $data
        ], 200);
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            $validated = $request->validate([
                'nama' => 'required|string|max:255',
                'is_pengajar' => 'required|boolean',
            ], [
                'nama.required' => 'Kolom nama wajib diisi.',
                'nama.string' => 'Kolom nama harus berupa teks.',
                'is_pengajar.required' => 'Status pengajar harus dipilih.',
                'is_pengajar.boolean' => 'Status pengajar harus bernilai true atau false.',
            ]);

            $data = StatusKaryawan::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil ditambahkan.',
                'data' => $data
            ], 201);
        
        
        } catch (\Illuminate\Validation\ValidationException $error) {
            return response()->json([
                'status' => false,
                'message' => 'Terdapat kesalahan pada input Anda. Mohon periksa dan lengkapi kembali',
                'errors' => $error->errors()
            ], 422);
            
        } catch (\Exception $e) {
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data.',
                'error' => $e->getMessage()
            ], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        
        try{

            $data = StatusKaryawan::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        } catch (\Exception $error) {
            return response()->json([
                'success' => false,
                'message' => $error->getMessage(),
                'error' => $error
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
                'nama' => 'sometimes|required|string',
                'is_pengajar' => 'sometimes|required|boolean',
            ], [
                'nama.required' => 'Kolom nama harus diisi.',
                'nama.string' => 'Kolom nama harus berupa teks.',
                'is_pengajar.required' => 'Status pengajar harus dipilih.',
                'is_pengajar.boolean' => 'Status pengajar harus berupa nilai true atau false.',
            ]);

            $data = StatusKaryawan::findOrFail($id);
    
            $filtered = collect($validated)->only((new StatusKaryawan)->getFillable())->toArray();
    
            $data->update($filtered);
    
            
            return response()->json([
                'success' => true,
                'message' => 'Data status karyawan baru berhasil ditambahkan',
                'data' => $data
            ], 200);

    
        } catch (\Illuminate\Validation\ValidationException $error) {
            return response()->json([
                'status' => false,
                'message' => 'Terdapat kesalahan pada input Anda. Mohon periksa dan lengkapi kembali',
                'errors' => $error->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $error) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan.'
            ], 404);

        } catch (\Exception $error) {
            return response()->json([
                'status' => false,
                'message' => $error->getMessage()
            ], 500);
        }


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy( $id)
    {

        try{

            $data = StatusKaryawan::findOrFail($id);
            $data->delete();
            return response()->json([ 
                'success' => true,
                'massage' => 'Data status karyawan berhasil dihapus',
                'data'=> $data->nama
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Data gagal dihapus karena Status masih digunakan. Periksa data karyawan atau data lain yang masih meeggunakan status yang dimaksud.',
                'error' => $e->getMessage()
            ], 409);        
        } catch (\Exception $error) {
            return response()->json([
                'success' => false,
                'message' => $error->getMessage(),
                'error' => $error
            ]);
        }
        
    }
}
