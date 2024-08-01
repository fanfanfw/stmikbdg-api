<section>
    <h5 class="mt-4 mb-3 fw-bold">(DSN) Get List Target / Mata Kuliah Diampu</h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/pengumuman/dosen/kelas-kuliah</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "targets": [
            {
                "target": 0,
                "ket_target": "All"
            },
            {
                "target": 3262,
                "kd_kampus": "A",
                "kampus": "Kampus Utama",
                "jns_mhs": "Reguler",
                "mk_id": 47,
                "kd_mk": "KD1621",
                "nm_mk": "Pemrograman 6",
                "semester": 6,
                "sks": 3
            }
        ]
    }
}</code></pre>
    <p>
        Target dengan nilai 0 digunakan untuk mengirim pengumuman ke semua mahasiswa.
    </p>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(DSN) Kirim Pengumuman</h5>
    <p>
        Untuk mengirim pengumuman, kirimkan permintaan ke <span class="badge bg-dark">/pengumuman/add</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span> dan kirimkan sebagai payload dalam JSON dengan format sebagai berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "target": 3262,
    "image": null,
    "message": "Test pengumuman mata kuliah"
}</code></pre>
    <p>Jika ingin mengirim gambar, maka lakukan hal yang sama seperti pada penjelasan untuk admin.</p>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(DSN) Get All Pengumuman</h5>
    <p>
        Semua pengumuman yang dapat dilihat oleh dosen adalah pengumuman miliknya sendiri dan pengumuman yang dikirim oleh admin. Kirimkan permintaan ke <span class="badge bg-dark">/pengumuman/dosen/list</span> menggunakan HTTP method <span class="badge bg-info">get</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_pengumuman": [
            {
                "pengumuman_id": 24,
                "target": 0,
                "pengirim": 1,
                "nm_pengirim": "Admin STMIK Bandung",
                "tgl_dikirim": "2024-06-17 19:58:54",
                "image": "http://stmikbdg-api.test/storage/pengumuman/images/Screenshot1.png",
                "message": "Test pengumuman oleh Admin",
                "avatar_pengirim": null
            },
            {
                "pengumuman_id": 21,
                "target": 0,
                "pengirim": 1,
                "nm_pengirim": "Admin STMIK Bandung",
                "tgl_dikirim": "2024-06-17 19:37:59",
                "image": null,
                "message": "Test pengumuman oleh Admin",
                "avatar_pengirim": null
            }
        ]
    }
}</code></pre>
    <p>
        Jika ingin dibuat pagination, maka tambahkan query parameter <span class="badge bg-secondary">page</span> dan isi nilai query tersebut dengan nomor halaman. Contohnya adalah <span class="badge bg-dark">/pengumuman/dosen/list?page=2</span>. Jika berhasil API akan memberikan respons seperti berikut:
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
        "prev_page_url": "http://stmikbdg-api.test/api/pengumuman/dosen/list?page=1",
        "next_page_url": null
    }
    }</code></pre>
</section>


<section>
    <h5 class="mt-5 mb-3 fw-bold">(DSN) Get List Pengumuman by Kelas Kuliah Id or Target</h5>
    <p>
        Jika ingin melihat list pengumuman pada kelas kuliah tertentu, kirimkan permintaan ke <span class="badge bg-dark">/pengumuman/dosen/list</span> dan tambahkan query <span class="badge bg-secondary">kelas_kuliah_id</span> yang diisi dengan nilai <b>target</b> yang ada di get list target. Contohnya <span class="badge bg-dark">/pengumuman/dosen/list?kelas_kuliah_id=3262</span> dan hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_pengumuman": [
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
