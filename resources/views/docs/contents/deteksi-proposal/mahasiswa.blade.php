<section>
<h5 class="mt-4 mb-3 fw-bold"> (MHS) Add Hasil Deteksi</h5>
    <p>
        Kirimkan permintaan dengan menggunakan HTTP method <span class="badge bg-info">post</span> ke <span class="badge bg-dark">/sikps/mahasiswa/deteksi/hasil/add</span> untuk menyimpan hasil deteksi. Kirimkan juga payload berupa JSON dengan format seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "nim": "1220001",
    "nm_mhs": "Suhaefi Fauzian",
    "judul": "Test Add Hasil Deteksi",
    "file": "proposal.pdf",
    "persentase_kemiripan": 2.5
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(MHS) Get List Hasil Deteksi</h5>
    <p>
        Untuk melihat semua hasil deteksi milik mahasiswa sendiri, kirimkan permintaan ke <span class="badge bg-dark">/sikps/mahasiswa/deteksi/hasil/list</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "hasil_deteksi": [
            {
                "similarity_id": 4,
                "nim": "1220001",
                "nm_mhs": "Suhaefi Fauzian",
                "judul": "Test Add Hasil Deteksi",
                "file": "proposal.pdf",
                "persentase_kemiripan": "2.5",
                "created_at": "2024-05-18 11:18:51"
            },
            // hasil deteksi lainnya...
        ]
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(MHS) Update Proposal Hasil Deteksi</h5>
    <p>
        Untuk memperbarui proposal yang pernah dideteksi kemiripannya, kirimkan permintaan ke <span class="badge bg-dark">/sikps/mahasiswa/deteksi/hasil/update</span> dengan menggunakan HTTP method <span class="badge bg-info">put</span> dan sertakan payload dalam bentuk JSON dengan format seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "similarity_id": 4,
    "judul": "Test Edit Proposal Hasil Deteksi",
    "file": "proposal_edit.pdf",
    "persentase_kemiripan": 3.6
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(MHS) Delete Proposal yang Telah Dideteksi</h5>
    <p>
        Untuk menghapus proposal yang pernah dideteksi, kirimkan permintaan ke <span class="badge bg-dark">/sikps/mahasiswa/deteksi/hasil/delete</span>. Gunakan HTTP method <span class="badge bg-info">delete</span> dan sertakan payload dalam bentuk JSON dengan format:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "similarity_id": 4
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(MHS) Get List Fingerprints</h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/sikps/mahasiswa/deteksi/fingerprints/list</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "fingerprints": [
            {
                "fingerprint_id": 4,
                "judul": "ANALISIS KUALITAS LAYANAN WEBSITE LMS STMIK BANDUNG TERHADAP KEPUASAN PENGGUNA MENGGUNAKAN METODE WEBQUAL 4.0 DAN IMPORTANCE PERFORMANCE ANALYSIS (IPA)",
                "file_dokumen": "http://103.119.67.164:18088/sikps/proposal/153/stream",
                "n_gram": "NUJLEg3KFPTkBuqx4CxtGLFfkaQU7PnyCEU4W0EShG98CmNBgTp5nqQZDmzXaN6qGw6GS7m9L6fvUqda4BC7hwgriahtSfNEfvWe5qWE0rZwVcVReYZaucukSmH2TQEd",
                "hashing": "WzN?JijQ%EWtbBtqM,H&bkG&eAwmVrpwm4i+b?}%(x95R4P(Tj-TLDpj(JnK:7VC-RJtVu[Kw7{1uk(fFizqG-=YKxG;tu9}F6cWx=tun}uCEzCw.{J$u+P)i3x{dmNr",
                "winnowing": "1jbau7WzeZawdYyUx8Tv67uwXvMYcNL54Z1Vf0N7eqBWcP6gbWip8rx4LYXYW6BA4CLkYqHTDtvYArbXnqCmbS8E2KnS1JGQcxxZgK7SjXVnK3C8DCg8DyFAvg62fPcN",
                "fingerprint": "ua8]Ggp,H+Hnuq)8@C?7yyBWABUUcjC!@7)7x.HX!2[j8pD/:Cx89F(7mTy0899}T-VMGCM7#!]iTC*_+#gC4:y,r2PigMG*tTt@(9xviyH19X;LN:WFqXF+wmU9pbG)",
                "total_fingerprint": 128,
                "total_ngram": 128,
                "total_hash": 128,
                "total_window": 128,
                "is_generated": false,
                "created_at": "2024-05-20 07:05:26.126"
            },
            // list fingerprint lainnya ...
        ]
    },
    "meta": {
        "current_page": 1,
        "per_page": 8,
        "total": 441,
        "last_page": 56,
        "next_page_url": "http://stmikbdg-api.test/api/sikps/mahasiswa/deteksi/fingerprints/list?page=2",
        "prev_page_url": null
    }
}</code></pre>
    <p>
        Karena bisa saja list fingerprint memiliki ukuran yang besar, sehingga JSON tidak dapat memuat seluruh fingerprint sekaligus. Maka dibuatkan pagination, perhatikanlah pada nilai <b>meta</b> yang ada pada response di atas. Contoh penggunaannya adalah dengan menambahkan query <span class="badge bg-secondary">page</span>, sehingga url menjadi seperti <span class="badge bg-dark">/sikps/mahasiswa/deteksi/fingerprints/list?page=1</span>.
    </p>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(MHS) Get Detail Proposal Hasil Deteksi</h5>
    <p>
        Untuk mendapatkan detail proposal yang sudah pernah dideteksi kemiripannya, lakukan permintaan dengan menggunakan HTTP method <span class="badge bg-info">get</span> ke <span class="badge bg-dark">/sikps/mahasiswa/deteksi/hasil/detail?similarity_id=11</span>. Ganti nilai <b>similarity_id</b> dengan similarity_id yang didapat dari get hasil deteksi.
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "similarity": {
            "similarity_id": 11,
            "nim": "1220001",
            "nm_mhs": "Suhaefi Fauzian",
            "judul": "Test Judul",
            "file": "proposal.pdf",
            "persentase_kemiripan": "24",
            "original_name": "proposal.pdf",
            "created_at": "2024-05-22 00:14:44.9"
        }
    }
}</code></pre>
</section>

<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ALL) Get Batas Kemiripan <span class="small text-success">*new</span></h5>
    <p>
        Untuk mendapatkan batas kemiripan proposal skripsi yang digunakan untuk menghitung kemiripan proposal yang diajukan dengan proposal lainnya, kirimkan permintaan ke <span class="badge bg-dark">/sikps/deteksi/similarities/limit</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil API akan memberikan respons seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "similarity_limit": {
            "similarity_limit_id": 1,
            "max_limit": 25
        }
    }
}</code></pre>
</section>
