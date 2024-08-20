<section class="mt-4">
    <h5 class="mb-3 fw-bold">(ALL) Get All Jenis Bimbingan <span class="small text-danger">*update</span></h5>
    <p>
        Untuk melihat jenis bimbingan yang telah tersedia, kirimkan permintaan ke <span class="badge bg-dark">/antrian/bimbingan/list/jenis-bimbingan</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil API akan memberikan respons seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "jenis_bimbingan": [
            {
                "jenis_bimbingan_id": 1,
                "nama": "Bimbingan Kerja Praktek"
            },
            {
                "jenis_bimbingan_id": 2,
                "nama": "Bimbingan Skripsi"
            }
        ]
    }
}</code></pre>
</section>
<section>
    <h5 class="mt-4 mb-3 fw-bold">(ADM) Get List Antrian Bimbingan <span class="small text-danger">*update</span></h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/antrian/bimbingan/list</span> dengan menggunakan HTTP metod <span class="badge bg-info">get</span>. Tambahkan query parameter <span class="badge bg-secondary">is_sudah</span> dengan isian nilai berupa boolean jika ingin memfilter hasil yang diinginkan, jika nilai yang dikirim false berarti list antrian bimbingannya adalah belum selesai. Penggunaannya seperti <span class="badge bg-dark">/antrian/bimbingan/list?is_sudah=false</span>. Response yang diberikan:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_antrian": [
            {
                "bimbingan_id": 9,
                "dosen_id": 2,
                "kd_dosen": "IF054",
                "nm_dosen": "MINA ISMU RAHAYU, M.T",
                "nim": "1220001",
                "nm_mhs": "SUHAEFI FAUZIAN",
                "tgl_bimbingan": "2024-06-07",
                "is_sudah": false,
                "jenis_bimbingan_id": 2,
                "judul": "Integrasi Sistem Informasi STMIK Bandung Berbasis REST API dan SSO",
                "created_at": "2024-08-07 17:47:50",
                "jenis_bimbingan": {
                    "jenis_bimbingan_id": 2,
                    "nama": "Bimbingan Skripsi"
                }
            }
        ]
    }
}</code></pre>
    <div class="alert alert-warning">
        <p>
            Anda juga dapat menggunakan dua query parameter yang telah disediakan untuk memfilter antrian berdasarkan nilai <b>is_sudah</b> dan nilai <b>kd_dosen</b>. Berikut adalah beberapa contoh penggunaan dari query parameter yang telah disediakan:
        </p>
        <ul>
            <li><span class="badge bg-dark">/antrian/bimbingan/list?is_sudah=true&kd_dosen=IF054</span></li>
            <li><span class="badge bg-dark">/antrian/bimbingan/list?is_sudah=false</span></li>
            <li><span class="badge bg-dark">/antrian/bimbingan/list?kd_dosen=IF054</span></li>
            <li><span class="badge bg-dark">/antrian/bimbingan/list?is_today=true</span></li>
        </ul>
    </div>
</section>
<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Get Detail Antrian Bimbingan</h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/antrian/bimbingan/detail/{bimbingan_id}</span>, ganti nilai <b>{bimbingan_id}</b> dengan nilai bimbingan_id yang ada saat get list antrian bimbingan. Kirimkan menggunakan HTTP method <span class="badge bg-info">get</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "antrian": {
            "bimbingan_id": 9,
            "dosen_id": 2,
            "kd_dosen": "IF054",
            "nm_dosen": "MINA ISMU RAHAYU, M.T",
            "nim": "1220001",
            "nm_mhs": "SUHAEFI FAUZIAN",
            "tgl_bimbingan": "2024-06-07",
            "is_sudah": false,
            "jenis_bimbingan_id": 2,
            "judul": "Integrasi Sistem Informasi STMIK Bandung Berbasis REST API dan SSO",
            "created_at": "2024-08-07 17:47:50",
            "jenis_bimbingan": {
                "jenis_bimbingan_id": 2,
                "nama": "Bimbingan Skripsi"
            }
        }
    }
}</code></pre>
</section>
<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Add Antrian Bimbingan</h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/antrian/bimbingan/add</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span> dan sertakan payload dalam format JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "nim": "1220001",
    "nm_mhs": "Suhaefi Fauzian",
    "dosen_pembimbing": "Mina Ismu Rahayu, M.T",
    "kd_dosen": "IF054",
    "tgl_bimbingan": "07-06-2024",
    "jenis_bimbingan_id": 2,
    "judul": "Integrasi Sistem Informasi STMIK Bandung Berbasis REST API dan SSO"
}</code></pre>
</section>
<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Update Status Antrian Bimbingan</h5>
    <p>
        Untuk memperbarui status antrian bimbingan, kirimkan permintaan dengan menggunakan HTTP method <span class="badge bg-blue">put</span> ke <span class="badge bg-dark">/antrian/bimbingan/status/update</span> dan sertakan payload dalam format JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "bimbingan_id": 5,
    "is_sudah": true
}</code></pre>
</section>
<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Update Data Antrian Bimbingan</h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/antrian/bimbingan/update</span> dengan menggunakan HTTP method <span class="badge bg-blue">put</span> dan sertakan payload dalam JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "bimbingan_id": 9,
    "nim": "1220001",
    "nm_mhs": "Suhaefi Fauzian",
    "dosen_pembimbing": "Mina Ismu Rahayu, M.T",
    "kd_dosen": "IF054",
    "tgl_bimbingan": "16-06-2024",
    "jenis_bimbingan_id": 2,
    "judul": "CONTOH UPDATE - INTEGRASI SISTEM INFORMASI STMIK BANDUNG BERBASIS REST API DAN SSO"
}</code></pre>
</section>
<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Hapus Antrian Bimbingan</h5>
    <p>
        Untuk menghapus antrian bimbingan tertentu, kirimkan permintaan ke <span class="badge bg-dark">/antrian/bimbingan/delete</span> dengan menggunakan HTTP method <span class="badge bg-info">delete</span> dan sertakan nilai <b>bimbingan_id</b> yang akan dihapus dalam payload dengan bentuk JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "bimbingan_id": 9
}</code></pre>
</section>
<section>
    <h5 class="mt-5 mb-3 fw-bold">(DSN) Get List Antrian Bimbingan <span class="small text-danger">*update</span></h5>
    <p>
        Digunakan oleh dosen untuk get list antrian bimbingan untuk mahasiswa yang dibimbing olehnya. Kirimkan permintaan ke <span class="badge bg-dark">/antrian/list/bimbingan</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. API akan memberikan response:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_antrian": [
            {
                "bimbingan_id": 9,
                "dosen_id": 2,
                "kd_dosen": "IF054",
                "nm_dosen": "MINA ISMU RAHAYU, M.T",
                "nim": "1220001",
                "nm_mhs": "SUHAEFI FAUZIAN",
                "tgl_bimbingan": "2024-06-16",
                "is_sudah": false,
                "jenis_bimbingan_id": 2,
                "judul": "INTEGRASI SISTEM INFORMASI STMIK BANDUNG BERBASIS REST API DAN SSO",
                "created_at": "2024-08-07 17:50:43",
                "jenis_bimbingan": {
                    "jenis_bimbingan_id": 2,
                    "nama": "Bimbingan Skripsi"
                }
            }
        ]
    }
}</code></pre>
    <div class="alert alert-warning">
        <p>
            Anda juga dapat menggunakan dua query parameter yang telah disediakan untuk memfilter antrian berdasarkan nilai <b>is_sudah</b>. Berikut adalah contoh penggunaan dari query parameter yang telah disediakan:
        </p>
        <ul>
            <li><span class="badge bg-dark">/antrian/list/bimbingan?is_sudah=false</span></li>
            <li><span class="badge bg-dark">/antrian/list/bimbingan?is_today=true</span></li>
        </ul>
    </div>
</section>
<section>
    <h5 class="mt-5 mb-3 fw-bold">(DSN) Update Status Antrian Bimbingan</h5>
    <p>
        Digunakan oleh dosen untuk mengubah status antrian bimbingan. Kirimkan permintaan ke <span class="badge bg-dark">/antrian/list/bimbingan</span> dengan menggunakan HTTP method <span class="badge bg-info">put</span> dan sertakan payload seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "bimbingan_id": 5,
    "is_sudah": false
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(MHS) Get List Dosen yang Ada di Sistem Antrian <span class="small text-success">*new</span></h5>
    <p>
        Untuk mendapatkan list dosen yang ada atau telah didaftarkan oleh Admin di sistem antrian, kirimkan permintaan ke <span class="badge bg-dark">/antrian/mahasiswa/bimbingan/list/dosen</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil API akan memberikan respons seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_dosen": [
            {
                "dosen_id": 2,
                "kd_dosen": "IF054",
                "nm_dosen": "MINA ISMU RAHAYU, M.T",
                "no_card": "02",
                "created_at": "2024-05-27 21:13:30"
            },
            {
                "dosen_id": 1,
                "kd_dosen": "RW",
                "nm_dosen": "RENA WIJAYA, S.KOM",
                "no_card": "01",
                "created_at": "2024-05-27 21:13:05"
            }
        ]
    }
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(MHS) Get List Antrian Bimbingan oleh Mahasiswa <span class="small text-success">*new</span></h5>
    <p>
        Untuk mendapatkan list antrian bimbingan milik mahasiswa sendiri (yang login), kirimkan permintaan ke <span class="badge bg-dark">/antrian/mahasiswa/bimbingan</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika ingin melihat antrian per tanggal hari ini, maka tambahkan query <span class="badge bg-secondary">is_today</span> dengan nilai <b>true</b>, sehingga penggunaannya menjadi <span class="badge bg-dark">/antrian/mahasiswa/bimbingan?is_today=true</span>. Berikut adalah contoh respons yang akan diberikan oleh API:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_antrian": [
            {
                "bimbingan_id": 11,
                "dosen_id": 2,
                "kd_dosen": "IF054",
                "nm_dosen": "MINA ISMU RAHAYU, M.T",
                "nim": "1220001",
                "nm_mhs": "SUHAEFI FAUZIAN",
                "tgl_bimbingan": "2024-08-20",
                "is_sudah": false,
                "jenis_bimbingan_id": 2,
                "judul": "Integrasi REST API dan SSO",
                "created_at": "2024-08-20 09:10:22"
            }
        ]
    }
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(MHS) Add Antrian Bimbingan <span class="small text-success">*new</span></h5>
    <p>
        Untuk menambahkan antrian baru kirimkan permintaan ke <span class="badge bg-dark">/antrian/mahasiswa/bimbingan/add</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span> dan sertakan payload body dalam bentuk JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "dosen_id": 2,
    "jenis_bimbingan_id": 2,
    "judul": "Integrasi REST API dan SSO",
    "tgl_bimbingan": "22-08-2024"
}</code></pre>
</section>
