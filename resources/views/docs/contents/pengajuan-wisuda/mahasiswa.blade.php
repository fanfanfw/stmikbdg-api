<section>
    <h5 class="mt-4 mb-3 fw-bold">
        (MHS) Get Judul Skripsi yang Diajukan di SIKPS
    </h5>
    <p>
        Untuk mendapatkan judul skripsi pada yang diajukan di SIKPS dan statusnya telah diterima, kirim permintaan ke <span class="badge bg-dark">/wisuda/pengajuan/skripsi/{nim}</span>, ganti <b>nim</b> dengan NIM milik Anda dan kirim dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Contohnya <span class="badge bg-dark">/wisuda/pengajuan/skripsi/1220001</span>, jika berhasil akan mengembalikan respons seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "skripsi_diajukan": {
            "judul": "INTEGRASI SISTEM STMIK BANDUNG DALAM BENTUK API (APPLICATION PROGRAMMING INTERFACE)"
        }
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">
        (MHS) Add Pengajuan Pendaftaran Wisuda
    </h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/wisuda/pengajuan</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span> dan sertakan payload dengan format JSON pada body seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "nim": "1220001",
    "nama": "Suhaefi Fauzian",
    "nik": "322xxxxxxxxx",
    "tempat_lahir": "Bandung",
    "tgl_lahir": "21-01-2002",
    "email": "suhaefi@simak.dev",
    "no_hp": "0857xxxxxx",
    "tgl_sidang_akhir": "20-07-2024",
    "file_bukti_pembayaran": "asdasd.pdf",
    "file_ktp": "ktp.pdf",
    "file_bukti_pembayaran_sumbangan": "asdasdlk.pdf",
    "file_ijazah": "ijazah.jpg",
    "judul_skripsi": "INTEGRASI SISTEM STMIK BANDUNG DALAM BENTUK API (APPLICATION PROGRAMMING INTERFACE)",
    "is_verified": false, // jika diisi true, aka pengajuan tidak bisa ubah lagi
}</code></pre>
    <div class="alert alert-warning">
        Untuk setiap file yang ada hanya kirimkan storage path dari file yang telah berhasil disimpan pada sisi frontend. Hal ini sesuai dengan kesepakatan saat diskusi.
    </div>

    <h5 class="mt-5 mb-3 fw-bold">
        (MHS) Get Detail Pengajuan
    </h5>
    <p>
        Untuk melihat detail pengajuan pendaftaran sendiri (sesuai dengan pemilik akun yang login ke sistem) kirimkan ke permintaan ke <span class="badge bg-dark">/wisuda/pengajuan/{nim}</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>, ganti <b>nim</b> dengan nim si pemilik akun yang login. Misalnya login sebagai Suhaefi Fauzian dengan NIM 1220001 maka URL-nya menjadi <span class="badge bg-dark">/wisuda/pengajuan/1220001</span>. Jika berhasil akan memberikan respons seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "pengajuan_wisuda": {
            "pengajuan_id": 3,
            "status_id": 1,
            "jadwal_wisuda_id": null,
            "file_id": 1,
            "nim": "1220001",
            "nama": "Suhaefi Fauzian",
            "nik": "322xxxxxxxxx",
            "tempat_lahir": "Bandung",
            "tgl_lahir": "21-01-2002",
            "email": "suhaefi@simak.dev",
            "no_hp": "0857xxxxxx",
            "judul_skripsi": "INTEGRASI SISTEM STMIK BANDUNG DALAM BENTUK API (APPLICATION PROGRAMMING INTERFACE)",
            "kd_status": "M",
            "ket_status": "Menunggu",
            "file_bukti_pembayaran": "asdasd.pdf",
            "file_ktp": "ktp.pdf",
            "file_bukti_pembayaran_sumbangan": "asdasdlk.pdf",
            "file_ijazah": "ijazah.jpg",
            "is_bayar": null,
            "is_ditolak": null,
            "is_verified": false,
            "ditolak_alasan": null,
            "tgl_sidang_akhir": "20-07-2024",
            "tgl_wisuda": null,
            "tgl_pengajuan": "2024-04-04 14:48:44"
        }
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">
        (MHS) Get Status Pengajuan
    </h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/wisuda/pengajuan/{nim}/status</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>, ganti <b>nim</b> dengan NIM mahasiswa pemilik akun yang login. Misalnya, login sebagai Suhaefi Fauzian dengan NIM 1220001, maka URL-nya menjadi <span class="badge bg-dark">/wisuda/pengajuan/1220001/status</span>. Hasilnya seperti di bawah ini:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "pengajuan_wisuda": {
            "pengajuan_id": 3,
            "kd_status": "M",
            "ket_status": "Menunggu",
            "tgl_pengajuan": "2024-04-04 14:48:44",
            "is_verified": false
        }
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">
        (MHS) Edit Pengajuan Mahasiswa
    </h5>
    <p>
        Untuk edit pengajuan kirimkan permintaan ke <span class="badge bg-dark">/wisuda/pengajuan/{nim}/update</span> dengan menggunakan HTTP method <span class="badge bg-info">PUT</span>, ganti <b>nim</b> dengan NIM milik Anda. Contohnya menjadi <span class="badge bg-dark">/wisuda/pengajuan/1220001/update</span>. Kemudian, kirimkan data dalam format JSON sebagai payload body seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "pengajuan_id": 3,
    "nim": "1220001",
    "nama": "Suhaefi Fauzian",
    "nik": "322xxxxxxxxx",
    "tempat_lahir": "Bandung",
    "tgl_lahir": "21-01-2002",
    "email": "suhaefi@simak.dev",
    "no_hp": "0857xxxxxx",
    "tgl_sidang_akhir": "20-07-2024",
    "file_bukti_pembayaran": "edit-asdasd.pdf",
    "file_ktp": "edit-ktp.pdf",
    "file_bukti_pembayaran_sumbangan": "edit-asdasdlk.pdf",
    "file_ijazah": "ijazah.jpg",
    "judul_skripsi": "EDIT JUDUL INTEGRASI SISTEM STMIK BANDUNG DALAM BENTUK API (APPLICATION PROGRAMMING INTERFACE)",
    "is_verified": true
}</code></pre>
    <p>
        Nilai <b>pengajuan_id</b> didapat dari get detail pengajuan.
    </p>
    <div class="alert alert-warning">
        <b>Peringatan!</b><br>
        Melakukan update atau edit terhadap pengajuan akan mengembalikan status menjadi menunggu.
    </div>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">
        (MHS) Get Jadwal Wisuda yang Sedang Aktif
    </h5>
    <p>
        Mahasiswa dapat melihat jadwal wisuda yang sedang aktif dengan cara kirimkan permintaan ke <span class="badge bg-dark">/wisuda/pengajuan/jadwal/aktif</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "jadwal_wisuda": {
            "jadwal_wisuda_id": 2,
            "tahun": 2024,
            "tgl_wisuda": "23-09-2024",
            "angkatan_wisuda": 33
        }
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(MHS) Verifikasi Data Pengajuan Wisuda</h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/wisuda/pengajuan/verifikasi</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span> dan sertakan request payload seperti di bawah ini. Sesuaikan nilai <b>pengajuan_id</b> dengan nilai yang didapat saat get detail pengajuan wisuda oleh mahasiswa.
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "pengajuan_id": 8,
    "is_verified": true
}</code></pre>
</section>
