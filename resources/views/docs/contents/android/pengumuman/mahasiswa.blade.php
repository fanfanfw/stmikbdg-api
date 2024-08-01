<section>
    <h5 class="mt-4 mb-3 fw-bold">(MHS) Subscribe Pengumuman / Kirim FCM Device Token</h5>
    <p>
        Kirimkan token FCM milik client android ke <span class="badge bg-dark">/pengumuman/mahasiswa/token/add</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span> dan payload seperti:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "token": "xxxx......"
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(MHS) Get List Mata Kuliah</h5>
    <p>
        Untuk melihat list mata kuliah tersedia pada pengumuman, kirimkan permintaan ke <span class="badge bg-dark">/pengumuman/mahasiswa/kelas-kuliah</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_kelas": [
            {
                "kelas_kuliah_id": 3262,
                "kd_kampus": "A",
                "kampus": "Kampus Utama",
                "jns_mhs": "Reguler",
                "mata_kuliah": {
                    "mk_id": 47,
                    "kd_mk": "KD1621",
                    "nm_mk": "Pemrograman 6",
                    "semester": 6,
                    "sks": 3
                },
                "dosen": {
                    "nm_dosen": "Mina Ismu Rahayu, M.T",
                    "gelar": "M.T"
                }
            }
        ]
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(MHS) Get All Pengumuman</h5>
    <p>
        Untuk melihat semua pengumuman yang dikirim oleh admin dan pengumuman mata kuliah yang sedang aktif. Kirimkan permintaan ke <span class="badge bg-dark">/pengumuman/mahasiswa/list</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_pengumuman": [
            {
                "pengumuman_id": 25,
                "target": 3262,
                "pengirim": 573,
                "nm_pengirim": "Mina Ismu Rahayu, M.T",
                "tgl_dikirim": "2024-06-17 20:29:52",
                "image": "http://stmikbdg-api.test/storage/pengumuman/images/Screenshot1.png",
                "message": "Test pengumuman mata kuliah",
                "avatar_pengirim": null
            },
            {
                "pengumuman_id": 24,
                "target": 0,
                "pengirim": 1,
                "nm_pengirim": "Admin STMIK Bandung",
                "tgl_dikirim": "2024-06-17 19:58:54",
                "image": "http://stmikbdg-api.test/storage/pengumuman/images/Screenshot1.png",
                "message": "Test pengumuman oleh Admin",
                "avatar_pengirim": null
            }
        ]
    }
}</code></pre>
    <p>
        Jika ingin dibuat pagination, maka tambahkan query parameter <span class="badge bg-secondary">page</span> dan isi nilai query tersebut dengan nomor halaman. Contohnya adalah <span class="badge bg-dark">/pengumuman/mahasiswa/list?page=2</span>. Jika berhasil API akan memberikan respons seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_oengumuman": [
            {
                "pengumuman_id": 12,
                "target": 0,
                "pengirim": 1,
                "nm_pengirim": "Admin",
                "tgl_dikirim": "2024-08-01 17:26:54",
                "image": null,
                "message": "Test pengumuman ke-1 oleh Admin",
                "avatar_pengirim": "admin.png"
            }
        ]
    },
    "meta": {
        "current_page": 2,
        "total_items": 6,
        "items_per_page": 5,
        "prev_page_url": "http://stmikbdg-api.test/api/pengumuman/mahasiswa/list?page=1",
        "next_page_url": null
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mt-3 fw-bold">(MHS) Get List Pengumuman by Kelas Kuliah Id</h5>
    <p>
        Untuk melihat list pengumuman pada mata kuliah tertentu, kirimkan permintaan ke <span class="badge bg-dark">/pengumuman/mahasiswa/list</span> dan tambahkan query <span class="badge bg-secondary">kelas_kuliah_id</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Contohnya <span class="badge bg-dark">/pengumuman/mahasiswa/list?kelas_kuliah_id=3262</span> dan hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_pengumuman": [
            {
                "pengumuman_id": 25,
                "target": 3262,
                "pengirim": 573,
                "nm_pengirim": "Mina Ismu Rahayu, M.T",
                "tgl_dikirim": "2024-06-17 20:29:52",
                "image": "http://stmikbdg-api.test/storage/pengumuman/images/Screenshot1.png",
                "message": "Test pengumuman mata kuliah",
                "avatar_pengirim": null
            },
            {
                "pengumuman_id": 17,
                "target": 3262,
                "pengirim": 573,
                "nm_pengirim": "Mina Ismu Rahayu, M.T",
                "tgl_dikirim": "2024-06-17 18:08:47",
                "image": null,
                "message": "Test pengumuman mata kuliah",
                "avatar_pengirim": null
            }
        ]
    }
}</code></pre>
</section>
