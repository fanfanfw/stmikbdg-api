<section class="mt-4">
    <div class="alert alert-warning">
        <b>Note:</b> Route-route API pada halaman ini digunakan untuk mendapatkan jadwal kuliah untuk mahasiswa. Route jadwal kuliah dapat diakses jika status KRS telah sah/disetujui. Selain melihat jadwal kuliah, digunakan juga untuk mengisi kehadiran dengan mengirimkan PIN yang diperoleh setelah dosen yang mengampu mata kuliah membuka kelasnya.
    </div>
</section>

<hr>

<section class="mt-4">
    <h5 class="mb-3 fw-bold">(MHS) Get Daftar Kelas Kuliah</h5>
    <p>
        Lakukan permintaan ke <span class="badge bg-dark">/kelas-kuliah/mahasiswa</span> menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil maka akan memberikan response dalam format JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "kelas_kuliah": [
            {
                "Selasa": [
                    {
                        "data_kelas": {
                            "kelas_kuliah_id": 3294,
                            "tahun_id": 348,
                            "jur_id": 23,
                            "mk_id": 137,
                            "join_kelas_kuliah_id": 3268,
                            "kjoin_kelas": true,
                            "kelas_kuliah": "A",
                            "jns_mhs": "R",
                            "sts_kelas": "B",
                            "pengajar_id": 562,
                            "join_jur": "MIS1"
                        },
                        "dosen": {
                            "dosen_id": 562,
                            "kd_dosen": "IF043",
                            "nm_dosen": "Dani Pradana Kartaputra, S.Si., M.T",
                            "gelar": ", S.Si., M.T"
                        },
                        "matakuliah": {
                            "mk_id": 137,
                            "kur_id": 83,
                            "kd_mk": "KU1813",
                            "nm_mk": "HAKI &Etika Profesi",
                            "semester": 8,
                            "sks": 2,
                            "sts_mk": "A",
                            "smt": 2,
                            "kd_kur": "IFS120"
                        },
                        "riwayat_presensi": [],
                        "riwayat_presensi_maks": 20,
                        "jadwal": {
                            "kelas_kuliah_id": 3294,
                            "mk_id": 137,
                            "mhs_id": 15534,
                            "dosen_id": 562,
                            "tanggal": "2024-05-28",
                            "jns_pert": "T1",
                            "jam": "09:30-11:10",
                            "kd_ruang": "31",
                            "nm_hari": "Selasa",
                            "tanggal_lokal": "28 Mei 2024"
                        },
                        "kelas_dibuka": false
                    }
                ]
            }
        ]
    }
}</code></pre>
    <p>
        Properti <b>Unknown</b> menandakan bahwa kelas kuliah yang ada di dalamnya belum memiliki jadwal, hal tersebut dapat dilihat juga pada nilai <b>jadwal</b> yang masih berisi <b>null</b>.
    </p>
    <p>
        Jika nilai <b>kjoin_kelas</b> adalah true, maka kelas kuliah tersebut digabungkan dengan kelas kuliah lain, kelasnya dapat dilihat pada nilai <b>join_kelas_kuliah_id</b>. Mengikuti tampilan pada aplikasi yang telah ada, maka wajib menampilkan nilai <b>join_jur</b> untuk menandakan bahwa kelas kuliah tersebut digabung dengan kelas lain.
    </p>
    <p>
        Untuk mempermudah hasil response, Anda dapat memfilter jadwal berdasarkan hari tertentu saja. Tambahkan query parameter <span class="badge bg-secondary">hari</span> dan isi dengan nama hari dalam bahasa Indonesia. Misalnya ingin mendapatkan jadwal kelas kuliah di hari Selasa saja, maka kirim request ke <span class="badge bg-dark">/kelas-kuliah/mahasiswa?hari=selasa</span>. Maka response akan menampilkan jadwal kuliah di hari Selasa saja.
    </p>
    <p>
        Nilai <b>kelas_dibuka</b> digunakan untuk mengetahui apakah kelas tersebut sedang dibuka atau sedang berlangsung, apabila nilai ini true maka mahasiswa dapat mengirim PIN kehadiran yang diperoleh dari dosen setelah membuka kelas.
    </p>
</section>

<section class="mt-5">
    <h5 class="mb-4 fw-bold">(MHS) Rekam Presensi - Single PIN (INPUT MANUAL)</h5>
    <p>
        Lakukan request ke <span class="badge bg-dark">/kelas-kuliah/mahasiswa/presensi</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span> dan sertakan payload pada body berupa:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "kelas_kuliah_id": 3251,
    "pin": 351670
}</code></pre>
    <p>
        Setelah berhasil mengirim PIN presensi, mahasiswa tidak dapat mengirim PIN presensi kehadiran lagi dan PIN presensi hanya dapat dikirim apabila kelasnya dibuka. Jika berhasil akan memberikan response:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "message": "Kehadiran Anda berhasil direkam"
}</code></pre>
</section>

<section class="mt-5">
    <h5 class="mb-3 fw-bold">(MHS) Rekam Presensi - QR Code - Single atau Unique PIN</h5>
    <p>
        Setelah berhasil scan QR Code yang diberikan oleh dosen, lakukanlah request di belakang layar setelah nilai berupa url didapat dari hasil scanning. Kemudian API akan memberikan response dalam json yang memberikan pesan bahwa presensi mahasiswa berhasil direkam atau tidak. Silahkan lihat pada bagian (DSN) Buka Kelas Kuliah di menu Kelas Kuliah bagian Dosen.
    </p>
</section>
