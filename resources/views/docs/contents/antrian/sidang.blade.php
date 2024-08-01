<section>
    <h5 class="mt-4 mb-3 fw-bold">(ADM) Get List Antrian Sidang</h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/antrian/sidang/list</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil API akan memberikan response:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_antrian": [
            {
                "sidang_id": 2,
                "dosen_id": 2,
                "kd_dosen": "IF054",
                "nm_dosen": "MINA ISMU RAHAYU, M.T",
                "nim": "1220001",
                "nm_mhs": "SUHAEFI FAUZIAN",
                "dosen_penguji1": "KHOIRIDA AELANI",
                "dosen_penguji2": "LINDA APRIYANTI",
                "tgl_sidang": "2024-07-31",
                "jenis_sidang_id": 1,
                "created_at": "2024-08-01 16:48:22",
                "jenis_sidang": {
                    "jenis_sidang_id": 1,
                    "nama": "Sidang Kerja Praktek"
                }
            }
        ]
    }
}</code></pre>
</section>
<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Get Detail Antrian Sidang</h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/antrian/sidang/detail/{sidang_id}</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Ganti nilai <b>{sidang_id}</b> dengan nilai sidang_id yang ada di list antrian sidang. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "antrian": {
            "sidang_id": 2,
            "dosen_id": 2,
            "kd_dosen": "IF054",
            "nm_dosen": "MINA ISMU RAHAYU, M.T",
            "nim": "1220001",
            "nm_mhs": "SUHAEFI FAUZIAN",
            "dosen_penguji1": "KHOIRIDA AELANI",
            "dosen_penguji2": "LINDA APRIYANTI",
            "tgl_sidang": "2024-07-31",
            "jenis_sidang_id": 1,
            "created_at": "2024-08-01 16:48:22",
            "jenis_sidang": {
                "jenis_sidang_id": 1,
                "nama": "Sidang Kerja Praktek"
            }
        }
    }
}</code></pre>
</section>
<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Add Antrian Sidang</h5>
    <p>
        Sertakan payload dalam bentuk JSON seperti di bawah ini, kemudian kirimkan ke <span class="badge bg-dark">/antrian/sidang/add</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span>. Contoh payload:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "nim": "1220001",
    "nm_mhs": "Suhaefi Fauzian",
    "dosen_pembimbing": "Mina Ismu Rahayu",
    "dosen_penguji1": "Khoirida Aelani",
    "dosen_penguji2": "Linda Apriyanti",
    "tgl_sidang": "31-07-2024",
    "jenis_sidang_id": 2
}</code></pre>
</section>
<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Update Data Antrian Sidang</h5>
    <p>
        Sertakan payload seperti di bawah ini dan kirimkan permintaan ke <span class="badge bg-dark">/antrian/sidang/update</span> dengan menggunakan HTTP method <span class="badge bg-info">put</span>. Contoh payload:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "sidang_id": 2,
    "nim": "1220001",
    "nm_mhs": "Suhaefi Fauzian",
    "dosen_pembimbing": "Mina Ismu Rahayu",
    "dosen_penguji1": "Khoirida Aelani",
    "dosen_penguji2": "Linda Apriyanti",
    "tgl_sidang": "31-07-2024",
    "jenis_sidang_id": 1
}</code></pre>
</section>
<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Delete Antrian Sidang</h5>
    <p>
        Untuk menghapus antrian sidang dari list, kirimkan permintaan ke <span class="badge bg-dark">/antrian/sidang/delete</span> dengan menggunakan HTTP method <span class="badge bg-info">delete</span> dan sertakan nilai sidang_id dalam payload seperti berikut ini:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "sidang_id": 1
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Get Jenis Sidang</h5>
    <p>
        Untuk mendapatkan list jenis sidang kirimkan permintaan ke <span class="badge bg-dark">/antrian/sidang/jenis</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil API akan memberikan respons seperti berikut:
    </p>
    <pre><code class="language-json">{
    "status": "success",
    "data": {
        "jenis_sidang": [
            {
                "jenis_sidang_id": 1,
                "nama": "Sidang Kerja Praktek"
            },
            {
                "jenis_sidang_id": 2,
                "nama": "Sidang Skripsi"
            }
        ]
    }
}</code></pre>
</section>
