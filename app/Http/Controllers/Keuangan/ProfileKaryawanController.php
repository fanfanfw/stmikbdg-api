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
        //
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
