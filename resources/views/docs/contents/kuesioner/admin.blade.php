<section>
    <h5 class="mt-4 mb-3 fw-bold">(ADM) Kuesioner Perkuliahan - Get Tahun Ajaran</h5>
    <p>
        Kirim permintaan ke <span class="badge bg-dark">/kuesioner/tahun-ajaran</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil API akan memberikan response berisi list tahun ajaran aktif seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "tahun_ajaran": [
            {
                "tahun_id": 280,
                "jur_id": 23,
                "tahun": 2022,
                "smt": 1,
                "jns_mhs": "R",
                "kd_kampus": "A",
                "sts_ta": "B",
                "uraian": "IFS1 / 2022 / Ganjil / Reguler",
                "ket_smt": "Ganjil",
                "ket_jns_mhs": "Reguler",
                "detail_jurusan": {
                    "jur_id": 23,
                    "fak_id": 0,
                    "kd_jur": "12",
                    "nama_jurusan": "S1 - TEKNIK INFORMATIKA",
                    "nm_singkat": "IFS1 ",
                    "prodi": "S1",
                    "k_aktif": true
                },
                "detail_kampus": {
                    "kd_kampus": "A",
                    "lokasi": "Kampus Utama",
                    "alamat": "Cikutra"
                },
                "kuesioner_open": true
            }
            // tahun ajaran aktif lainnya disembunyikan
        ]
    }
}</code></pre>
    <p>
        Perhatikan nilai <b>kuesioner_open</b>, jika isinya adalah true maka kuesioner perkuliahan pada tahun tersebut telah dibuka, artinya setiap mahasiswa yang berada pada tahun ajaran tersebut sudah dapat mengisi kuesioner setiap matkul yang ada pada tahun ajaran itu. Dan jika isinya adalah false, berarti kuesioner perkuliahan pada tahun ajaran tersebut belum dibuka dan mahasiswa tidak dapat mengisi kuesioner.
    </p>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Kuesioner Perkuliahan - Get Matkul Berdasarkan Tahun Ajaran</h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/perkuliahan</span> dan tambahkan query parameter <span class="badge bg-secondary">tahun_id</span>, <span class="badge bg-secondary">jns_mhs</span>, dan <span class="badge bg-secondary">kd_kampus</span>. Nilai-nilai query parameter tersebut diperoleh dari tahun ajaran yang tersedia pada response get tahun ajaran untuk kuesioner. Contoh penggunaannya menjadi seperti <span class="badge bg-dark">/kuesioner/perkuliahan?tahun_id=280&jns_mhs=R&kd_kampus=A</span>, jika permintaan berhasil API akan memberikan response seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "matakuliah": [
            {
                "mk_id": 81,
                "kur_id": 83,
                "jur_id": 23,
                "tahun_id": 280,
                "kd_mk": "IF1101",
                "nm_mk": "Fisika 1",
                "semester": 1,
                "sks": 2,
                "kd_kampus": "A",
                "jns_mhs": "R",
                "detail_kuesioner": {
                    "tahun_id": 280,
                    "kelas_kuliah_ids": "2146",
                    "total_mahasiswa": 29,
                    "total_mahasiswa_mengisi_kuesioner": 0,
                    "kelas_kuliah": "A",
                    "sts_open": false
                },
                "detail_dosen": {
                    "dosen_id": 581,
                    "kd_dosen": "IF062",
                    "nm_dosen": "KOESHANDOYO ARIONO,",
                    "gelar": "S.T"
                }
            },
            {
                "mk_id": 82,
                "kur_id": 83,
                "jur_id": 23,
                "tahun_id": 280,
                "kd_mk": "IF1102",
                "nm_mk": "Pengantar Teknik Informatika",
                "semester": 1,
                "sks": 2,
                "kd_kampus": "A",
                "jns_mhs": "R",
                "detail_kuesioner": {
                    "tahun_id": 280,
                    "kelas_kuliah_ids": "2147",
                    "total_mahasiswa": 26,
                    "total_mahasiswa_mengisi_kuesioner": 0,
                    "kelas_kuliah": "A",
                    "sts_open": false
                },
                "detail_dosen": {
                    "dosen_id": 706,
                    "kd_dosen": "KA   ",
                    "nm_dosen": "Khoirida Aelani, M.T.",
                    "gelar": "M.T"
                }
            }
            // matkul lain disembunyikan
        ]
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Kuesioner Perkuliahan - Buka Kuesioner</h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/perkuliahan/open</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span> dan sertakan payload dalam body dengan format JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "tahun_id": 280
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Hasil Kuesioner Perkuliahan - Get List Tahun Ajaran</h5>
    <p>
        Digunakan untuk filter pada bagian tahun ajaran yang telah tersedia pada kuesioner perkuliahan, jika list tahun ajaran tidak ada atau API memberikan kode error 404 berarti belum ada sama sekali hasil kuesioner perkuliahannya. Untuk mendapatkan list ini, kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/perkuliahan/hasil/tahun-ajaran</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "kuesioner_perkuliahan": {
            "list_tahun": [
                {
                    "tahun_id": 335,
                    "jur_id": 23,
                    "tahun": 2023,
                    "smt": 1,
                    "jns_mhs": "R",
                    "kd_kampus": "A",
                    "sts_ta": "B",
                    "uraian": "IFS1 / 2023 / Ganjil / Reguler",
                    "ket_smt": "Ganjil",
                    "ket_jns_mhs": "Reguler",
                    "detail_jurusan": {
                        "jur_id": 23,
                        "fak_id": 0,
                        "kd_jur": "12",
                        "nama_jurusan": "S1 - TEKNIK INFORMATIKA",
                        "nm_singkat": "IFS1 ",
                        "prodi": "S1",
                        "k_aktif": true
                    },
                    "detail_kampus": {
                        "kd_kampus": "A",
                        "lokasi": "Kampus Utama",
                        "alamat": "Cikutra"
                    }
                },
                ...
            ]
        }
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Hasil Kuesioner Perkuliahan - Get List Semester by tahun_id</h5>
    <p>
        Digunakan untuk get list semester yang digunakan sebagai filter, untuk mendapatkan list dari semester memerlukan nilai <b>tahun_id</b> yang didapat dari list tahun ajaran dan digunakan sebagai query paramater. Kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/perkuliahan/hasil/semester</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Digabung dengan query paramater <b>tahun_id</b>, sehingga menjadi <span class="badge bg-dark">/kuesioner/perkuliahan/hasil/semester?tahun_id=335</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "kuesioner_perkuliahan": {
            "list_semester": [
                {
                    "tahun_id": 335,
                    "semester": 7
                },
                ...
            ]
        }
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Hasil Kuesioner Perkuliahan - Get List Dosen by tahun_id dan semester</h5>
    <p>
        Digunakan untuk mendapat list nama dosen yang ada pada kuesioner perkuliahan berdasarkan pada nilai <b>tahun_id</b> dan <b>semester</b> yang dipilih. Kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/perkuliahan/hasil/dosen</span> dan tambahkan query parameter <span class="badge bg-secondary">tahun_id</span> dan <span class="badge bg-secondary">semester</span>, kirim menggunakan HTTP method <span class="badge bg-info">get</span>. Contoh penggunaannya adalah <span class="badge bg-dark">/kuesioner/perkuliahan/hasil/dosen?tahun_id=335&semester=7</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "kuesioner_perkuliahan": {
            "list_dosen": [
                {
                    "kuesioner_perkuliahan_mahasiswa_id": 17,
                    "pengajar_id": 573,
                    "nm_dosen": "Mina Ismu Rahayu, M.T"
                },
                {
                    "kuesioner_perkuliahan_mahasiswa_id": 18,
                    "pengajar_id": 562,
                    "nm_dosen": "Dani Pradana Kartaputra, S.Si., M.T"
                }
            ]
        }
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Hasil Kuesioner Perkuliahan - Get List Mata Kuliah yang Diampu Dosen</h5>
    <p>
        Gunakan nilai <b>tahun_id</b>, <b>semester</b>, dan <b>dosen_id</b> yang dipilih berdasarkan dari list sebelumnya sebagai query parameter untuk mendapatkan list mata kuliah yang diampu dosen dan telah tersedia pada kuesioner perkuliahan. Gunakan HTTP method <span class="badge bg-info">get</span> dengan mengirimkan permintaan ke <span class="badge bg-dark">kuesioner/perkuliahan/hasil/matkul</span>.
    </p>
    <p>
        Contoh penggunaannya adalah <span class="badge bg-dark">/kuesioner/perkuliahan/hasil/matkul?tahun_id=335&semester=7&dosen_id=573</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "kuesioner_perkuliahan": {
            "tahun": 2023,
            "list_matkul": [
                {
                    "kuesioner_perkuliahan_mahasiswa_id": 17,
                    "kelas_kuliah_id": 3092,
                    "mk_id": 130,
                    "semester": 7,
                    "nm_mk": "Pengolahan Citra",
                    "nm_dosen": "Mina Ismu Rahayu, M.T",
                    "kuesioner": {
                        "total_mahasiswa": 28,
                        "total_mahasiswa_mengisi_kuesioner": 1
                    }
                },
                ...
            ]
        }
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Hasil Kuesioner Perkuliahan - Get List Mahasiswa yang Mengisi Kuesioner Mata Kuliah</h5>
    <p>
        Digunakn untuk mendapatkan list mahasiswa yang telah mengisi kuesioner perkuliahan pada suatu mata kuliah berdasarkan pada kelas yang diajar oleh dosen tertentu. Kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/perkuliahan/hasil/mahasiswa</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span> dan sertakan nilai <b>kelas_kuliah_id</b> yang didapat dari hasil get poin sebelumnya sebagai query parameter. Contohnya <span class="badge bg-dark">/kuesioner/perkuliahan/hasil/mahasiswa?kelas_kuliah_id=3092</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "kuesioner_perkuliahan": {
            "tahun": 2023,
            "list_mahasiswa": [
                {
                    "kuesioner_perkuliahan_mahasiswa_id": 17,
                    "tahun_id": 335,
                    "kelas_kuliah_id": 3092,
                    "semester": 7,
                    "nim": "1220001",
                    "nm_mk": "Pengolahan Citra",
                    "nm_dosen": "Mina Ismu Rahayu, M.T"
                },
                ...
            ]
        }
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Hasil Kuesioner Perkuliahan - Get Jawaban Kuesioner Mahasiswa</h5>
    <p>
        Untuk mendapatkan jawaban dari kuesioner perkuliahan milik mahasiswa, kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/perkuliahan/hasil/jawaban</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span> dan sertakan nilai <b>kuesioner_perkuliahan_mahasiswa_id</b> yang didapat dari daftar di poin sebelumnya sebagai query parameter. Contohnya <span class="badge bg-dark">/kuesioner/perkuliahan/hasil/jawaban?kuesioner_perkuliahan_mahasiswa_id=17</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "kuesioner_perkuliahan": {
            "kuesioner_perkuliahan_id": 4,
            "kuesioner_perkuliahan_mahasiswa_id": 17,
            "tahun": 2023,
            "nim": "1220001",
            "nm_mk": "Pengolahan Citra",
            "nm_dosen": "Mina Ismu Rahayu, M.T",
            "semester": 7,
            "pertanyaan_dan_jawaban": {
                "Teknologi Pembelajaran": [
                    {
                        "pertanyaan_id": 1,
                        "jenis_pertanyaan_id": 1,
                        "kelompok_pertanyaan_id": 1,
                        "kd_jenis_pertanyaan": "P",
                        "jenis": "Perkuliahan",
                        "kelompok": "Teknologi Pembelajaran",
                        "pertanyaan": "Apakah seluruh proses perkuliahan telah memiliki aktivitas kelas pembelajaran online (Google Meet/Zoom/Discord) pada saat jam perkuliahan?",
                        "jawaban": {
                            "point_id": 4,
                            "kd_point": "S",
                            "ket_point": "Setuju",
                            "mutu": 4
                        }
                    },
                    ...
                ],
                ...
            }
        }
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Hasil Kuesioner Perkuliahan - Get Rata-rata Jawaban Kuesioner untuk Satu Mata Kuliah</h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/perkuliahan/hasil/rata-rata</span> dengan menggunakan query parameter <span class="badge bg-secondary">tahun_id</span> dan <span class="badge bg-secondary">kelas_kuliah_id</span>, kirimkan dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Contohnya, <span class="badge bg-dark">/kuesioner/perkuliahan/hasil/rata-rata?tahun_id=335&kelas_kuliah_ids=3092</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "kuesioner_perkuliahan": {
            "tahun_id": 335,
            "kd_mk": "IF1710",
            "nm_mk": "Pengolahan Citra",
            "sks": 3,
            "semester": 7,
            "dosen": "Mina Ismu Rahayu, M.T",
            "total_mahasiswa": 28,
            "total_mahasiswa_mengisi_kuesioner": 1,
            "pertanyaan_dan_jawaban": {
                "Teknologi Pembelajaran": [
                    {
                        "pertanyaan_id": 1,
                        "jenis_pertanyaan_id": 1,
                        "kelompok_pertanyaan_id": 1,
                        "kd_jenis_pertanyaan": "P",
                        "jenis": "Perkuliahan",
                        "kelompok": "Teknologi Pembelajaran",
                        "pertanyaan": "Apakah seluruh proses perkuliahan telah memiliki aktivitas kelas pembelajaran online (Google Meet/Zoom/Discord) pada saat jam perkuliahan?",
                        "jawaban": {
                            "point_id": 4,
                            "kd_point": "S",
                            "ket_point": "Setuju",
                            "mutu": 4,
                            "rata_rata": 4
                        }
                    },
                    ...
                ],
                ...
            }
        }
    }
}</code></pre>
</section>

<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Hasil Kuesioner Perkuliahan - Get Hasil By Tahun Ajaran</h5>
    <p>
        Digunakan untuk get hasil kuesioner untuk setiap mata kuliah yang ada di tahun ajaran. Gunakan get list tahun ajaran untuk hasil kuesioner perkuliahan agar list tahun ajaran yang didapat adalah list tahun ajaran yang telah tersedia atau pernah dibuka oleh admin sistem kuesioner.
    </p>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/perkuliahan/hasil/v2/rata-rata?tahun_id=348</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span> dan sesuaikan nilai <span class="badge bg-secondary">tahun_id</span>. API menyediakan dua response berbeda untuk hasil kuesioner ini. Response pertama adalah list matkul beserta kuesioner dan rata-rata jawaban untuk setiap pertanyaan, seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "kuesioner_perkuliahan": [
            {
                "mk_id": 137,
                "nm_mk": "HAKI &Etika Profesi",
                "nm_dosen": "Dani Pradana Kartaputra, S.Si., M.T",
                "semester": 8,
                "total_mahasiswa": 29,
                "total_mahasiswa_mengisi_kuesioner": 2,
                "pertanyaan_dan_jawaban": {
                    "Teknologi Pembelajaran": [
                        {
                            "pertanyaan_id": 1,
                            "jenis_pertanyaan_id": 1,
                            "kelompok_pertanyaan_id": 1,
                            "kd_jenis_pertanyaan": "P",
                            "jenis": "Perkuliahan",
                            "kelompok": "Teknologi Pembelajaran",
                            "pertanyaan": "Apakah pembelajaran menggunakan media zoom efektif untuk pembelajaran?",
                            "jawaban": {
                                "point_id": 2,
                                "kd_point": "S",
                                "ket_point": "Setuju",
                                "mutu": 4,
                                "rata_rata": 3.5
                            }
                        },
                        ...
                    ],
                    ...
                }
            },
            {
                "mk_id": 145,
                "nm_mk": "Cloud Computing",
                "nm_dosen": "M. RIZKI PRATAMA. S, S.Kom",
                "semester": 7,
                "total_mahasiswa": 50,
                "total_mahasiswa_mengisi_kuesioner": 2,
                "pertanyaan_dan_jawaban": {
                    "Teknologi Pembelajaran": [
                        {
                            "pertanyaan_id": 1,
                            "jenis_pertanyaan_id": 1,
                            "kelompok_pertanyaan_id": 1,
                            "kd_jenis_pertanyaan": "P",
                            "jenis": "Perkuliahan",
                            "kelompok": "Teknologi Pembelajaran",
                            "pertanyaan": "Apakah pembelajaran menggunakan media zoom efektif untuk pembelajaran?",
                            "jawaban": {
                                "point_id": 2,
                                "kd_point": "S",
                                "ket_point": "Setuju",
                                "mutu": 4,
                                "rata_rata": 3.5
                            }
                        },
                        ...
                    ],
                    ...
                }
            }
        ]
    }
}</code></pre>
    <p>
        Response kedua adalah jika ingin digunakan langsung ke dalam diagram seperti Pie Chart, sehingga tidak perlu diolah lagi pada sisi client untuk ditampilkan sebagai chart. Untuk mendapatkan response kedua, maka tambahkan query parameter <span class="badge bg-secondary">chart</span> dan isikan dengan nilai true. Sehingga URL-nya menjadi, <span class="badge bg-dark">/kuesioner/perkuliahan/hasil/v2/rata-rata?tahun_id=348&chart=true</span>. Hasilnya:
    </p>
    <pre><code class="language json bg-primary-subtle">{
    "status": "success",
    "data": {
        "kuesioner_perkuliahan": [
            {
                "mk_id": 137,
                "nm_mk": "HAKI &Etika Profesi",
                "nm_dosen": "Dani Pradana Kartaputra, S.Si., M.T",
                "semester": 8,
                "total_mahasiswa": 29,
                "total_mahasiswa_mengisi_kuesioner": 2,
                "pertanyaan_dan_jawaban": [
                    {
                        "label": "Teknologi Pembelajaran",
                        "value": 25
                    },
                    {
                        "label": "Kegiatan Awal Pembelajaran",
                        "value": 22.41
                    },
                    {
                        "label": "Pelaksanaan Pembelajaran",
                        "value": 27.3
                    },
                    {
                        "label": "Penilaian Hasil Belajar",
                        "value": 25.29
                    }
                ]
            },
            {
                "mk_id": 145,
                "nm_mk": "Cloud Computing",
                "nm_dosen": "M. RIZKI PRATAMA. S, S.Kom",
                "semester": 7,
                "total_mahasiswa": 50,
                "total_mahasiswa_mengisi_kuesioner": 2,
                "pertanyaan_dan_jawaban": [
                    {
                        "label": "Teknologi Pembelajaran",
                        "value": 26.06
                    },
                    {
                        "label": "Kegiatan Awal Pembelajaran",
                        "value": 18.61
                    },
                    {
                        "label": "Pelaksanaan Pembelajaran",
                        "value": 28.85
                    },
                    {
                        "label": "Penilaian Hasil Belajar",
                        "value": 26.47
                    }
                ]
            }
        ]
    }
}</code></pre>
</section>

<!--<h5 class="mt-5 mb-3 fw-bold">(ADM) Kuesioner Kegiatan - Add Kuesioner</h5>
<p>
    Berdasarkan hasil diskusi, bahwa kegiatan kampus tidak berdasarkan pada tahun ajaran dan belum terdapat tabel yang menyimpan data kegiatan tersebut. Sehingga untuk membuka kuesionernya perlu ditambahkan data kegiatan kampus terlebih dahulu.
</p>
<p>
    Kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/kegiatan/add</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span> dan sertakan payload seperti berikut:
</p>
<pre><code class="language-json bg-primary-subtle">{
"tanggal_mulai": "19-01-2024",
"tanggal_akhir": "20-02-2024",
"organisasi": "HIMA SISTEM INFORMASI",
"kegiatan": "INSPIRA-SI SPORT"
}</code></pre>

<h5 class="mt-5 mb-3 fw-bold">(ADM) Kuesioner Kegiatan - Get List Kuesioner</h5>
<p>
    Untuk mendapatkan list kuesioner kegiatan yang telah ada, kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/kegiatan/list</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Hasilnya:
</p>
<pre><code class="language-json bg-primary-subtle">{
"status": "success",
"data": {
    "kuesioner_kegiatan": [
        {
            "kuesioner_kegiatan_id": 1,
            "tahun": 2024,
            "tanggal_mulai": "2024-01-19",
            "tanggal_akhir": "2024-02-20",
            "organisasi": "HIMA SISTEM INFORMASI",
            "kegiatan": "INSPIRA-SI SPORT"
        },
        // kuesioner kegiatan lain disembunyikan
    ]
}
}</code></pre>

<h5 class="mt-5 mb-3 fw-bold">(ADM) Kuesioner Kegiatan - Get Rata-rata Jawaban</h5>
<br>
<p>
    Untuk melihat rata-rata jawaban dari satu kuesioner kegiatan yang telah diisi oleh mahasiswa, kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/kegiatan/jawaban/rata-rata?kuesioner_kegiatan_id={nilai_kuesioner_kegiatan_id}</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Ganti <b>{nilai_kuesioner_kegiatan_id}</b> dengan nilai dari properti kuesioner_kegiatan_id yang didapat di response get list kuesioner kegiatan. Contohnya, <span class="badge bg-dark">/kuesioner/kegiatan/jawaban/rata-rata?kuesioner_kegiatan_id=1</span>. Jika berhasil API akan memberikan response seperti:
</p>
<pre><code class="language-json bg-primary-subtle">{
"status": "success",
"data": {
    "kuesioner_kegiatan": {
        "kuesioner_kegiatan_id": 1,
        "tahun": 2024,
        "tanggal_mulai": "2024-01-19",
        "tanggal_akhir": "2024-02-20",
        "organisasi": "HIMA SISTEM INFORMASI",
        "kegiatan": "INSPIRA-SI SPORT",
        "total_mahasiswa_mengisi_kuesioner": 1,
        "pertanyaan_dan_jawaban": [
            {
                "pertanyaan_id": 23,
                "jenis_pertanyaan_id": 2,
                "kelompok_pertanyaan_id": 4,
                "kd_jenis_pertanyaan": "K",
                "jenis": "Kegiatan Kampus",
                "kelompok": "Kepuasan Mahasiswa",
                "pertanyaan": "Apakah anda setuju kegiatan ini bermanfaat bagi anda?",
                "jawaban": {
                    "point_id": 4,
                    "kd_point": "S",
                    "ket_point": "Setuju",
                    "mutu": 4
                }
            },
            // pertanyaan dan jawaban lainnya disembunyikan
        ]
    }
}
}</code></pre>-->

<!--<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Get List Jenis Pertanyaan</h5>
    <p>
        Untuk melihat jenis pertanyaan yang tersedia, kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/pertanyaan/jenis</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil akan memberikan response:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "jenis_pertanyaan": [
            {
                "jenis_pertanyaan_id": 1,
                "nama": "Perkuliahan",
                "kd_jenis_pertanyaan": "P"
            },
            {
                "jenis_pertanyaan_id": 2,
                "nama": "Kegiatan Kampus",
                "kd_jenis_pertanyaan": "K"
            }
        ]
    }
}</code></pre>
</section>-->

<!--<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Get List Kelompok Pertanyaan</h5>
    <p>
        Setelah jenis, pertanyaan-pertanyaan yang ada dikelompokkan lagi menjadi beberapa bagian. Untuk melihat terdapat kelompok pertanyaan apa saja, kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/pertanyaan/kelompok</span> dan tambahkan query parameter <span class="badge bg-secondary">jenis_id</span>. Query parameter <span class="badge bg-secondary">jenis_id</span> didapat dari list jenis pertanyaan. Contoh lengkapnya adalah seperti <span class="badge bg-dark">/kuesioner/pertanyaan/kelompok?jenis_id=1</span>. Response:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "kelompok_pertanyaan": [
            {
                "kelompok_pertanyaan_id": 1,
                "jenis_pertanyaan_id": 1,
                "jenis": "Perkuliahan",
                "nama": "Teknologi Pembelajaran"
            },
            {
                "kelompok_pertanyaan_id": 2,
                "jenis_pertanyaan_id": 1,
                "jenis": "Perkuliahan",
                "nama": "Kegiatan Awal Pembelajaran"
            },
            {
                "kelompok_pertanyaan_id": 3,
                "jenis_pertanyaan_id": 1,
                "jenis": "Perkuliahan",
                "nama": "Pelaksanaan Pembelajaran"
            }
        ]
    }
}</code></pre>
</section>-->

<!--<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Get List Pertanyaan</h5>
    <p>
        Untuk melihat list pertanyaan yang tersedia, kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/pertanyaan</span> dengan menyertakan query parameter <span class="badge bg-secondary">jenis_id</span>. Kirimkan menggunakan HTTP method <span class="badge bg-info">get</span>. Contohnya, <span class="badge bg-dark">/kuesioner/pertanyaan?jenis_id=2</span>. Hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_pertanyaan": {
            "Kepuasan Mahasiswa": [
                {
                    "pertanyaan_id": 23,
                    "jenis_pertanyaan_id": 2,
                    "kelompok_pertanyaan_id": 4,
                    "kd_jenis_pertanyaan": "K",
                    "jenis": "Kegiatan Kampus",
                    "kelompok": "Kepuasan Mahasiswa",
                    "pertanyaan": "Apakah anda setuju kegiatan ini bermanfaat bagi anda?"
                },
                // pertanyaan lain disembunyikan
            ]
        }
    }
}</code></pre>
</section>-->

<!--<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Get Detail Pertanyaan</h5>
    <p>
        Untuk mendapatkan detail pertanyaan tertentu, kirimkan permintaan dengan menggunakan HTTP method <span class="badge bg-info">get</span> ke <span class="badge bg-dark">/kuesioner/pertanyaan/detail/{pertanyaan_id}</span>. Ganti <b>{pertanyaan_id}</b> dengan nilai <b>pertanyaan_id</b> yang didapat saat get list pertanyaan. Seperti <span class="badge bg-dark">/kuesioner/pertanyaan/detail/23</span>, hasilnya:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "pertanyaan": {
            "pertanyaan_id": 23,
            "jenis_pertanyaan_id": 2,
            "kelompok_pertanyaan_id": 4,
            "kd_jenis_pertanyaan": "K",
            "jenis": "Kegiatan Kampus",
            "kelompok": "Kepuasan Mahasiswa",
            "pertanyaan": "Apakah anda setuju kegiatan ini bermanfaat bagi anda?"
        }
    }
}</code></pre>
</section>-->

<!--<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Add Pertanyaan</h5>
    <p>
        Untuk menambahkan pertanyaan baru, kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/pertanyaan/add</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span>. Kirimkan payload seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "jenis_pertanyaan_id": 2,
    "kelompok_pertanyaan_id": 4,
    "pertanyaan": "Seberapa setuju Anda bahwa kegiatan kampus memberikan manfaat yang sebanding dengan waktu dan energi yang Anda investasikan?"
}</code></pre>
</section>-->

<!--<section>
    <h5 class="mt-5 mb-3 fw-bold">(ADM) Edit Pertanyaan</h5>
    <p>
        Kirimkan permintaan ke <span class="badge bg-dark">/kuesioner/pertanyaan/edit</span> dengan menggunakan HTTP method <span class="badge bg-info">put</span>. Kirimkan payload seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "pertanyaan_id": 33,
    "jenis_pertanyaan_id": 2,
    "kelompok_pertanyaan_id": 4,
    "pertanyaan": "Ada pertanyaan buat diedit gak?"
}</code></pre>
</section>-->
