@extends('docs.template.index')
@section('docs_contents')
<h4><b>#</b> Users</h4>
<hr>
<div class="m-2">
    <h5 class="mt-4">User Profile dan Account</h5>
    <hr>
    <p>
        Untuk mendapatkan data profile dan akun pengguna aktif atau pengguna terautentikasi dilakukan dengan cara mengirim permintaan ke <span class="badge bg-dark">/users/me</span> menggunakan HTTP method <span class="badge bg-info">get</span> dan jangan lupa sertakan <b>headers</b> seperti yang telah dijelaskan pada bagian Authentications.
    </p>
    <p>Jika permintaan berhasil, maka akan memberikan response seperti berikut untuk akun yang memiliki role sebagai mahasiswa:</p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "profile": {
            "kd_kampus": "A ",
            "mhs_id": 15534,
            "angkatan_id": 76,
            "kelas": "A",
            "nim": "1220001",
            "jk": "L",
            "dosen_id": 573,
            "jur_id": 23,
            "jns_mhs": "R",
            "sts_mhs": "A",
            "angkatan": 2020,
            "nama_jurusan": "S1 - TEKNIK INFORMATIKA",
            "dosen_wali": "Mina Ismu Rahayu, M.T",
            "tmp_lahir": "BANDUNG",
            "tgl_lahir": "2002-01-21",
            "masuk_semester": 0,
            "masuk_tahun": 2020,
            "alamat": "Desa Cibiru Wetan, Kec. Cileunyi, Kab. Bandung, Jawa Barat 40625",
            "nmr_hp": "0895-xxxx-xxxx",
            "tgl_daftar": "2020-04-04",
            "nama": "SUHAEFI FAUZIAN"
        },
        "account": {
            "email": "suhaefi@simak.dev",
            "image": "http://stmikbdg-api.test/storage/users/images/college_student.png",
            "is_dosen": false,
            "is_admin": false,
            "is_mhs": true,
            "is_dev": true,
            "is_doswal": false,
            "is_prodi": false
        }
    }
}</code></pre>
    <p>
        Berikut ini adalah response untuk akun yang memiliki role dosen:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "profile": {
            "dosen_id": 573,
            "kd_dosen": "IF054",
            "gelar": "M.T",
            "golongan": null,
            "jns_dosen": "1",
            "sts_dosen": "A",
            "jur_id": null,
            "kd_jab": null,
            "honor_per_sks": null,
            "nidn": null,
            "inisial": "MN   ",
            "profile_ht": null,
            "nama": "Mina Ismu Rahayu",
            "nama_dan_gelar": "Mina Ismu Rahayu, M.T"
        },
        "account": {
            "email": "mina@simak.dev",
            "image": "http://stmikbdg-api.test/storage/users/images/college_student.png",
            "is_dosen": true,
            "is_admin": true,
            "is_mhs": false,
            "is_dev": true,
            "is_doswal": true,
            "is_prodi": true
        }
    }
}</code></pre>
    <p>
        Perhatikan pada nilai <b>account</b>. Bagian tersebut berisi informasi akun yang digunakan untuk mengakses API dan juga role dari si pemilik. Anda dapat menyimpan setiap nilai dalam properti <b>account</b> untuk digunakan dalam mengatur akses ke setiap fitur yang dibuat pada sisi Front-End.
    </p>
    <p>
        Demikian juga pada sisi API, kami menggunakan setiap nilai yang ada untuk menentukan akses pengguna ke endpoint. <b>Jadi tidak semua pengguna dapat mengakses endpoint yang ada di API ini</b>.
    </p>

    {{-- Ganti Password --}}
    <h5 class="mt-4">Update Password</h5>
    <hr>
    <p>
        Lakukan permintaan ke <span class="badge bg-dark">/users/me/password</span> dengan menggunakan HTTP method <span class="badge bg-info">put</span> dan mengirim payload dengan format:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "current_password": "password_saat_ini",
    "new_password": "password_baru"
}</code></pre>
    <p>
        Apabila berhasil maka akan memberikan response yang terdapat pesan yang menyatakan bahwa perubahan password telah berhasil dengan status code <span class="badge bg-success">200</span>. Setelah password berhasil diubah sangat direkomendasikan untuk logout kemudian login kembali atau autentikasi ulang.
    </p>
    <div class="alert alert-warning">
        <b>Perlu diperhatikan!</b> Kemungkinan API digunakan oleh beberapa sistem, sehingga mengganti password artinya mengganti password pengguna yang bersangkutan pada seluruh sistem yang menggunakan API ini.
    </div>

    {{-- Ganti foto profile --}}
    <h5 class="mt-4">Update Foto Profile</h5>
    <hr>
    <p>
        Lakukan permintaan ke <span class="badge bg-dark">/users/me/image</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span> dan kirimkan payload berupa file dengan ekstensi png atau jpg, jenis payloadnya adalah form data. Maksimal ukuran file gambarnya adalah 1 MB.
    </p>
    <p>
        Jika berhasil diganti maka akan memberikan response yang menyisipkan url gambar baru sebagai nilai dari properti <b>image</b>.
    </p>
    <pre><code class="language-json bg-primary-subtle">{
        "status": "success",
        "message": "Foto profile berhasil diperbaharui.",
        "data": {
            "image": "http://stmikbdg-api.test/storage/users/images/qWXlHi9GRct9DCsNK4QFzgognGQvS8re6RGlpZnR.jpg"
        }
}</code></pre>
    <div class="alert alert-warning">
        <b>Perlu diperhatikan!</b> Kemungkinan API digunakan oleh beberapa sistem, sehingga mengganti foto profile artinya mengganti foto profile pengguna yang bersangkutan pada seluruh sistem yang menggunakan API ini.
    </div>

    {{-- Daftar web yang bisa diakses user --}}
    <h5 class="mt-4">Get Daftar Web yang Bisa Diakses</h5>
    <hr>
    <p>
        Digunakan untuk get list web yang bisa diakses oleh user. Kirim permintaan ke <span class="badge bg-dark">/sso/sites/user-access</span> menggunakan HTTP method <span class="badge bg-info">get</span>, jika berhasil akan mengembalikan response seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "sites": [
            {
                "url": "http://stmikbdg-acl.test/",
                "name": "Sistem Access Control Level"
            },
            {
                "url": "http://stmikbdg-deteksi.test/",
                "name": "Sistem Deteksi Kemiripan Proposal Skripsi"
            }
        ]
    }
}</code></pre>
    <p>
        List web yang bisa diakses oleh user terdapat pada properti <b>sites</b>.
    </p>
</div>
@endsection
