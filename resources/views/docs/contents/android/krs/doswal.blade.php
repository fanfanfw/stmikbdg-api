<section>
    <h5 class="mt-4 mb-3 fw-bold">(DOSWAL) Get List KRS Mahasiswa</h5>
    <p>
        Digunakan untuk mendapatkan daftar KRS terbaru yang mahasiswa ajukan. Daftar krs yang muncul adalah milik mahasiswa yang dosen walinya adalah user yang terautentikasi sebagai dosen wali yang bersangkutan. Daftar yang muncul diurutkan berdasarkan pengajuan KRS paling baru.
    </p>
    <p>
        Lakukan request ke <span class="badge bg-dark">/krs/mahasiswa/list</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span> untuk mendapatkan semua daftar KRS. Tambahkan query <span class="badge bg-secondary">search</span> jika ingin mendapatkan KRS berdasarkan NIM atau nama mahasiswa. Contohnya <span class="badge bg-dark">/krs/mahasiswa/list?search=1220001</span>.
    </p>
    <p>Hasilnya adalah:</p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_krs_mahasiswa": [
            {
                "mhs_id": 15534,
                "nim": "1220001",
                "nm_mhs": "SUHAEFI FAUZIAN",
                "jns_mhs": "R",
                "sts_mhs": "A",
                "kd_kampus": "A ",
                "kelas": "A",
                "masuk_tahun": 2020,
                "dosen_id": 573,
                "krs_id_last": 4615,
                "krs": [
                    {
                        "krs_id": 4615,
                        "tahun_id": 348,
                        "mhs_id": 15534,
                        "nmr_krs": "015/12/KR2/23",
                        "tanggal": "2024-01-12",
                        "semester": 8,
                        "sts_krs": "S",
                        "kd_kampus": "A"
                    }
                ]
            }
        ]
    }
}</code></pre>
    <p>
        Apabila ingin membagi data mahasiswa menjadi per halaman atau memiliki pagination, maka tambahkan <span class="badge bg-secondary">page</span> dan isi nilainya dengan halaman keberapa yang akan didapatkan datanya. Untuk defaultnya mohon isikan dengan nilai <b>1</b>. Sehingga secara penuh urlnya menjadi <span class="badge bg-dark">/krs/mahasiswa/list?page=1</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_krs_mahasiswa": [
            {
                "mhs_id": 16639,
                "nim": "3222305",
                "nm_mhs": "APRIYANI",
                "jns_mhs": "R",
                "sts_mhs": "A",
                "kd_kampus": "A ",
                "kelas": "A",
                "masuk_tahun": 2022,
                "dosen_id": 573,
                "krs_id_last": 4928,
                "krs": [
                    {
                        "krs_id": 4928,
                        "tahun_id": 349,
                        "mhs_id": 16639,
                        "nmr_krs": "094/32/KR2/23",
                        "tanggal": "2024-02-23",
                        "semester": 4,
                        "sts_krs": "S",
                        "kd_kampus": "A"
                    }
                ]
            }
            // mahasiswa lain disembunyikan
        ]
    },
    "meta": {
        "current_page": 1,
        "total_items": 267,
        "items_per_page": 10,
        "prev_page_url": null,
        "next_page_url": "http://stmikbdg-api.test/krs/mahasiswa/list?page=2"
    }
}</code></pre>
    <p>
        Jika menambahkan <span class="badge bg-secondary">page</span> maka akan terdapat properti baru pada response yang bernama <b>meta</b>. Properti tersebut menyimpan informasi mengenai posisi halaman, total item per halaman, total halaman, hingga url ke halaman berikutnya.
    </p>

    {{-- Filter by tahun masuk --}}
    <p>
        Anda juga dapat menggunakan filter bernama <b>tahun_masuk</b> untuk mendapatkan list KRS mahasiswa berdasarkan pada tahun masuknya atau angkatan. Misalnya <span class="badge bg-dark">/krs/mahasiswa/list?page=1&tahun_masuk=2018</span>.
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_krs_mahasiswa": [
            {
                "mhs_id": 3713,
                "nim": "1218005",
                "nm_mhs": "FAJAR APRILIAN",
                "jns_mhs": "R",
                "sts_mhs": "A",
                "kd_kampus": "A ",
                "kelas": "A",
                "masuk_tahun": 2018,
                "dosen_id": 573,
                "krs_id_last": 4819,
                "krs": [
                    {
                        "krs_id": 4819,
                        "tahun_id": 348,
                        "mhs_id": 3713,
                        "nmr_krs": "080/12/KR2/23",
                        "tanggal": "2024-01-15",
                        "semester": 12,
                        "sts_krs": "S",
                        "kd_kampus": "A"
                    }
                ]
            }
            // mahasiswa lain disembunyikan
        ]
    },
    "meta": {
        "current_page": 1,
        "total_items": 10,
        "items_per_page": 10,
        "prev_page_url": null,
        "next_page_url": null
    }
}</code></pre>

    {{-- Filter by semester --}}
    <p>
        Sekarang, Anda juga dapat menambahkan filter semester untuk melihat list KRS mahasiswa yang ada. Bahkan Anda juga dapat menggabungkannya dengan filter tahun masuk. Contohnya <span class="badge bg-dark">/krs/mahasiswa/list?page=1&tahun_masuk=2019&semester=8</span>.
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_krs_mahasiswa": [
            {
                "mhs_id": 3737,
                "nim": "1219008",
                "nm_mhs": "MUHAMAD RIZAL SEPTIANSYAH",
                "jns_mhs": "K",
                "sts_mhs": "A",
                "kd_kampus": "A ",
                "kelas": "A",
                "masuk_tahun": 2019,
                "dosen_id": 573,
                "krs_id_last": 4630,
                "krs": [
                    {
                        "krs_id": 4630,
                        "tahun_id": 350,
                        "mhs_id": 3737,
                        "nmr_krs": "011/12/KK2/23",
                        "tanggal": "2024-01-12",
                        "semester": 8,
                        "sts_krs": "S",
                        "kd_kampus": "A"
                    }
                ]
            }
            // mahasiswa lain disembunyikan
        ]
    },
    "meta": {
        "current_page": 1,
        "total_items": 5,
        "items_per_page": 10,
        "prev_page_url": null,
        "next_page_url": null
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(DOSWAL) Get Detail KRS Mahasiswa Menggunakan mhs_id and krs_id</h5>
    <p>
        Untuk mendapatkan detail dari satu KRS mahasiswa lakukan permintaan ke <span class="badge bg-dark">/krs/mahasiswa</span> dan tambahkan query pada urlnya, yaitu <span class="badge bg-secondary">mhs_id</span> yang nilainya dipilih dari list di atas dan <span class="badge bg-secondary">krs_id</span> yang merupakan nilai dari <b>krs_last_id</b> yang dipilih dari list di atas yang berada pada object yang sama dengan <b>mhs_id</b>-nya.
    </p>
    <p>
        Sehingga lengkapnya adalah seperti <span class="badge bg-dark">/krs/mahasiswa?mhs_id=15534&krs_id=4550</span> kemudian kirim menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil, response yang diberikan adalah sebagai berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "mahasiswa": {
            "mhs_id": 15534,
            "nim": "1220001",
            "nama": "SUHAEFI FAUZIAN",
            "jurusan": {
                "jur_id": 23,
                "nama_jurusan": "S1 - TEKNIK INFORMATIKA",
                "nm_singkat": "IFS1 "
            },
            "krs": {
                "krs_id": 4550,
                "tahun_id": 335,
                "nmr_krs": "089/12/KR1/23",
                "tanggal": "2024-02-20",
                "semester": 7,
                "sts_krs": "P",
                "kd_kampus": "A",
                "kd_chanel": "A",
                "pengajuan_catatan": "Draft KRS",
                "ditolak_tanggal": null,
                "ditolak_alasan": null,
                "ditolak_stlh_sah": null,
                "krs_matkul": [
                    {
                        "krs_mk_id": 34658,
                        "mk_id": 129,
                        "sts_mk_krs": "A",
                        "tgl_perubahan": "2024-02-20",
                        "kd_mk": "IF1709",
                        "nm_mk": "Analisis Numerik",
                        "sks": 2,
                        "k_disetujui": false,
                        "nilai_akhir": null
                    },
                    {
                        "krs_mk_id": 34659,
                        "mk_id": 130,
                        "sts_mk_krs": "A",
                        "tgl_perubahan": "2024-02-20",
                        "kd_mk": "IF1710",
                        "nm_mk": "Pengolahan Citra",
                        "sks": 3,
                        "k_disetujui": false,
                        "nilai_akhir": null
                    }
                ]
            }
        }
    }
}</code></pre>
    <p>
        Kemudian nilai <b>mhs_id</b>, <b>krs_id</b>, dan setiap <b>krs_mk_id</b> yang terdapat pada <b>krs_matkul</b> nantinya digunakan untuk memperbaharui KRS mahasiswa tersebut.
    </p>
</section>

<section>
    {{-- Update pengajuan KRS mahasiswa oleh dosen wali --}}
    <h5 class="mt-5 mb-3 fw-bold">(DOSWAL) Update KRS Mahasiswa</h5>
    <p>
        Gunakan nilai <b>mhs_id, krs_id</b>, dan <b>krs_id</b> yang didapat setelah get detail mahasiswa untuk memperbaharui KRS mahasiswa. Status KRS yang telah diajukan ditandai dengan <b>sts_krs</b> bernilai <b>P</b>. Berikutnya adalah beberapa status yang dapat digunakan untuk memperbaharui KRS mahasiswa.
    </p>
    <ul>
        <li><b>D</b> -> draft atau saat ditolak</li>
        <li><b>S</b> -> sah atau sudah disetujui</li>
    </ul>
    <p>
        Untuk memperbaharuinya lakukan request ke <span class="badge bg-dark">/krs/mahasiswa</span> menggunakan HTTP method <span class="badge bg-info">put</span> dengan mengirim payload seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "mhs_id": 15534,
    "krs_id": 4550,
    "sts_krs": "D", // D = Draft; S = Setujui;
    "ditolak_alasan": "Matkul terlalu sedikit", // Jika sts_krs D maka alasan wajib diisi
    "krs_matkul": [
        {
            "krs_mk_id": 34658,
            "k_disetujui": true
        },
        {
            "krs_mk_id": 34659,
            "k_disetujui": true
        }
    ]
}</code></pre>
    <p>
        Jika request berhasil maka akan memberikan response seperti di bawah ini:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "message": "KRS mahasiswa berhasil diperbaharui",
    "data": {
        "krs_id": 4550
    }
}</code></pre>
</section>

<section class="mt-4">
    <h5 class="mb-3 fw-bold">(DOSWAL) Get List Tahun Angkatan untuk Filter KRS <span class="small text-success">*new</span></h5>
    <p>
        Untuk mendapatkan list tahun angakatan yang digunakan untuk filter KRS, kirimkan permintaan ke <span class="badge bg-dark">/krs/mahasiswa/filter/angkatan</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil API akan memberikan respons seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "filter_angkatan": [
            {
                "angkatan": 2023
            },
            {
                "angkatan": 2022
            },
            {
                "angkatan": 2021
            },
            {
                "angkatan": 2020
            },
            {
                "angkatan": 2019
            },
            {
                "angkatan": 2011
            }
        ]
    }
}</code></pre>
</section>

<section class="mt-4">
    <h5 class="mb-3 fw-bold">(DOSWAL) Get List Semester untuk Filter KRS <span class="small text-success">*new</span></h5>
    <p>
        Digunakan untuk mendapatkan list semester yang digunakan untuk filter krs mahasiswa, kirimkan permintaan ke <span class="badge bg-dark">/krs/mahasiswa/filter/semester</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil API akan memberikan respons seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "filter_semester": [
            {
                "semester": 2
            },
            {
                "semester": 4
            },
            {
                "semester": 6
            },
            {
                "semester": 7
            },
            {
                "semester": 8
            },
            {
                "semester": 12
            }
        ]
    }
}</code></pre>
</section>
