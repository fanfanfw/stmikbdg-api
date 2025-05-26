<?php

namespace Database\Seeders;

use App\Models\Keuangan\FungsionalKaryawan;
use App\Models\Keuangan\GolonganKaryawan;
use App\Models\Keuangan\StatusKaryawan;
use App\Models\Keuangan\JabatanKaryawan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Faker\Factory as Faker;
use App\Models\Keuangan\ProfileKaryawan;
use Illuminate\Support\Facades\DB;

class ProfileKaryawanSeeder extends Seeder
{

    // private function clearTable()
    // {
    //     DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    //     ProfileKaryawan::truncate();
    //     DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    // }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // koosngkan table karyawan
        DB::table('keuangan.k_karyawan')->truncate();

        $faker = Faker::create();
        
        $idStatus = StatusKaryawan::pluck('id')->toArray();
        $idJabatan = JabatanKaryawan::pluck('id')->toArray();
        $idGolongan = GolonganKaryawan::pluck('id')->toArray();
        $idFungsional = FungsionalKaryawan::pluck('id')->toArray();


        foreach (range(1, 25) as $index) {
            ProfileKaryawan::create([
                'nama'          => $faker->name,
                'nidn_nuptk' => $faker->numberBetween(100000, 999999),
                'nik' =>  $faker->boolean(80) ? $faker->numberBetween(10000, 99999) : null,
                'nama'  => $faker->name,
                'email' => $faker->safeEmail,
                'no_telepon' => '+62' . $faker->numerify('8#########'), 
                'pendidikan_terakhir'  => $faker->randomElement(['sd', 'smp', 'sma', 'd1-d2','d3','sarjana','magister','doktor']),
                'alamat' => $faker->address,
                'tmt' => $faker->dateTimeBetween('1990-01-01', '2015-12-31')->format('Y-m-d'),
                'no_rekening' =>  $faker->numberBetween(1000000000, 9999999999),
                'nama_bank' => $faker->randomElement(['bri', 'bca', 'bni', 'mandiri','btn','ocbc']),
                'id_status' => $faker->randomElement($idStatus),
                'id_jabatan' => $faker->randomElement($idJabatan),
                'id_golongan' => $faker->randomElement($idGolongan),
                'id_fungsional' => $faker->randomElement($idFungsional),
                'status_menikah' =>  rand(1, 100) <= 70, // 70% verified



                // 'id_personal'   => $faker->numberBetween(10000, 99999), // Wajib, 5 angka
                // 'id_personal2'  => $faker->boolean(60) ? $faker->numberBetween(10000, 99999) : null, // Opsional
                // 'posisi_id'     => $faker->randomElement($posisiIds), // Relasi dinamis
                // 'divisi_nama'   => $faker->randomElement($divisiNamaList), // Data non-relasional dari tabel lain
            ]);
        }
    }

   
}
