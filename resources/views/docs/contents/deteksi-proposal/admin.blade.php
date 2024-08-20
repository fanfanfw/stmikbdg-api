<section>
    <h5 class="mt-4 mb-3 fw-bold">(ADM) Add Fingerprint Proposal</h5>
    <p>
        Untuk menambahkan hasil proposal yang telah diubah menjadi fingerprint, kirimkan permintaan ke <span class="badge bg-dark">/sikps/deteksi/fingerprints/add</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span> dan sertakan payload dalam bentuk JSON dengan format seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "fingerprints": [
        {
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
            "created_at": "2024-05-18 04:49:10.326"
        }
    ]
}</code></pre>
    <p>
        Nilai <b>is_generated</b> digunakan untuk menentukan apakah fingerprint berasal dari list proposal yang terdapat pada API SIKPS yang berjalan sekarang atau bukan. Jika bukan berarti proposal ditambahkan secara manual melalui fitur tambah proposal di sistem deteksi langsung, maka isikan <b>is_generated</b> dengan nilai false. Jika setiap fingerprint berasal dari API SIKPS maka isikan nilai <b>is_generated</b> dengan nilai true.
    </p>
    <p>
        Isilah nilai <b>created_at</b> dengan waktu yang merupakan timestamp dan telah diformat ke ISO 8601. Jika menggunakan Laravel, cara cepatnya cukup isikan dengan <b>now()</b>.
    </p>
</section>

<section>
    <h5 class="mt-4">(ADM) Get List Fingerprint Proposal Tersedia</h5>
    <p>
        Untk melihat semua fingerprint proposal yang tersedia, kirimkan permintaan ke <span class="badge bg-dark">/sikps/deteksi/fingerprints/list</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Hasilnya akan seperti:
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
                "created_at": "2024-05-18 04:49:10.326"
            },
            // data fingerprint lainnya ...
        ]
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Get Detail Fingerprint Proposal</h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/sikps/deteksi/fingerprints/detail?fingerprint_id=1594</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span> untuk mendapatkan detail umum dari fingerprint yang ada. Ganti nilai <b>fingerprint_id</b> dengan nilai fingerprint_id yang tersedia setelah get list fingerprint.
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "fingerprint": {
            "fingerprint_id": 1594,
            "judul": "Test Ini Isinya Judul",
            "file_dokumen": "http://sistem-deteksi.test/proposal/stream?file=sL5HIcypgysrHj9FmoKOuLygisNu43nA.pdf"
        }
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Update Fingerprint Proposal</h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/sikps/deteksi/fingerprints/update</span> dengan menggunakan HTTP method <span class="badge bg-info">put</span> dan sertakan payload dalam bentuk JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "fingerprint_id": 4,
    "judul": "Test Edit Fingerprint Proposal",
    "file_dokumen": "test_edit.pdf",
    "n_gram": "NUJLEg3KFPTkBuqx4CxtGLFfkaQU7PnyCEU4W0EShG98CmNBgTp5nqQZDmzXaN6qGw6GS7m9L6fvUqda4BC7hwgriahtSfNEfvWe5qWE0rZwVcVReYZaucukSmH2TQEd",
    "hashing": "WzN?JijQ%EWtbBtqM,H&bkG&eAwmVrpwm4i+b?}%(x95R4P(Tj-TLDpj(JnK:7VC-RJtVu[Kw7{1uk(fFizqG-=YKxG;tu9}F6cWx=tun}uCEzCw.{J$u+P)i3x{dmNr",
    "winnowing": "1jbau7WzeZawdYyUx8Tv67uwXvMYcNL54Z1Vf0N7eqBWcP6gbWip8rx4LYXYW6BA4CLkYqHTDtvYArbXnqCmbS8E2KnS1JGQcxxZgK7SjXVnK3C8DCg8DyFAvg62fPcN",
    "fingerprint": "ua8]Ggp,H+Hnuq)8@C?7yyBWABUUcjC!@7)7x.HX!2[j8pD/:Cx89F(7mTy0899}T-VMGCM7#!]iTC*_+#gC4:y,r2PigMG*tTt@(9xviyH19X;LN:WFqXF+wmU9pbG)",
    "total_fingerprint": 128,
    "total_ngram": 128,
    "total_hash": 128,
    "total_window": 128
}</code></pre>
    <p>
        Apabila <b>file_dokumen</b> tidak berubah maka jangan sertakan nilai tersebut dan cukup sertakan nilai <b>judul</b> saja.
    </p>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Hapus Fingerprint Proposal</h5>
    <p>
        Untuk menghapus fingerprint proposal yang tersedia pada list, kirimkan permintaan ke <span class="badge bg-dark">/sikps/deteksi/fingerprints/delete</span> dengan menggunakan HTTP method <span class="badge bg-info">delete</span> dan sertakan nilai <b>fingerprint_id</b> sebagai payload dalam bentuk JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "fingerprint_id": 4
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Get List Hasil Deteksi Mahasiswa</h5>
    <p>
        Untuk melihat semua riwayat hasil deteksi mahasiswa, kirimkan permintaan ke <span class="badge bg-dark">/sikps/deteksi/riwayat</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Hasilnya:
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
            }
        ]
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Delete Semua Generated Fingerprint</h5>
    <p>
        Digunakan untuk menghapus semua fingerprint generate dari web SIKPS. Perlu diperhatikan, penggunaan route ini hanya dilakukan jika ingin generate ulang fingerprint proposal dari web SIKPS. Kirimkan permintaan ke <span class="badge bg-dark">/sikps/deteksi/fingerprints/generated/delete</span> dengan menggunakan HTTP method <span class="badge bg-info">delete</span>.
    </p>
</section>

<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Update Batas Kemiripan <span class="small text-success">*new</span></h5>
    <p>
        Untuk memperbarui batas kemiripan proposal yang digunakan, kirimkan permintaan ke <span class="badge bg-dark">/sikps/deteksi/similarities/limit/update</span> dengan menggunakan HTTP method <span class="badge bg-info">put</span> dan sertakan payload body dalam bentuk JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "max_limit": 20
}</code></pre>
</section>
