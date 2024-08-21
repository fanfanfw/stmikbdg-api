<section class="mt-4">
    <h5 class="mb-3 fw-bold">Get Antrian Bimbingan Hari Ini</h5>
    <p>
        Untuk mendapatkan list antrian bimbingan hari ini, kirimkan permintaan ke <span class="badge bg-dark">/antrian/public/bimbingan?is_today=true</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil API akan memberikan respons seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_antrian": {
            "MINA ISMU RAHAYU, M.T": [
                {
                    "bimbingan_id": 13,
                    "dosen_id": 2,
                    "kd_dosen": "IF054",
                    "nm_dosen": "MINA ISMU RAHAYU, M.T",
                    "nim": "1220001",
                    "nm_mhs": "SUHAEFI FAUZIAN",
                    "tgl_bimbingan": "2024-08-21",
                    "is_sudah": false,
                    "jenis_bimbingan_id": 2,
                    "judul": "Integrasi REST API dan SSO",
                    "created_at": "2024-08-21 13:45:35"
                }
            ]
        }
    }
}</code></pre>
</section>
