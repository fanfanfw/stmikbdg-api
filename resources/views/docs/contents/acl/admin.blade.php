<section class="mt-4">
    <h5 class="mb-3 fw-bold">(ADM) Staff - Get Daftar Akun dengan Role Staff</h5>
    <p>
        Untuk melihat daftar akun yang memiliki role staff, kirimkan permintaan ke <span class="badge bg-dark">/sso/staff/list</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil, API akan memberikan response seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "total_staff": 2,
        "list_staff": [
            {
                "staff_id": 5,
                "user_id": 1,
                "nama": "Mina Ismu Rahayu, M.T",
                "email": "mina@simak.dev",
                "no_hp": "-",
                "image": "staff.png",
                "is_marketing": false,
                "is_akademik": true,
                "is_baak": false,
                "is_secretary": false
            },
            {
                "staff_id": 4,
                "user_id": 9,
                "nama": "Eva Diah Novitasari",
                "email": "eva@simak.dev",
                "no_hp": "-",
                "image": "staff.png",
                "is_marketing": false,
                "is_akademik": false,
                "is_baak": false,
                "is_secretary": true
            }
        ]
    }
}</code></pre>
    <p>
        Jika ingin mendapatkan daftar akun staff berdasarkan bagian tertentu, misalnya hanya daftar akun untuk staff akademik saja. Maka, tambahkan query parameter <span class="badge bg-secondary">job</span> dan isikan dengan salah satu nilai berikut: <b>is_akademik</b>, <b>is_marketing</b>, dan <b>is_baak</b>. Contoh lengkap penggunaannya adalah <span class="badge bg-dark">/sso/staff/list?job=is_akademik</span>.
    </p>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Staff - Get Detail Account</h5>
    <p>
        Untuk melihat detail akun milik staf tertentu, kirimkan permintaan ke <span class="badge bg-dark">/sso/staff/detail?staff_id=4</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span> dan jangan lupa sesuaikan nilai query parameter <span class="badge bg-secondary">staff_id</span>. Jika berhasil API akan memberikan response seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "user": {
            "account": {
                "id": 9,
                "kd_user": "MHS-1220286",
                "email": "eva.diah@simak.dev",
                "image": "staff.png",
                "is_staff": true
            },
            "profile": {
                "staff_id": 4,
                "user_id": 9,
                "nama": "Eva Diah Novitasari",
                "email": "eva@simak.dev",
                "no_hp": "-",
                "image": "staff.png",
                "is_secretary": true
            },
            "site_access": [
                {
                    "user_id": 9,
                    "site_id": 9,
                    "email": "eva.diah@simak.dev,
                    "url": "http://stmikbdg-surat.test/",
                    "name": "Sistem Surat Masuk dan Keluar"
                }
            ]
        }
    }
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Staff - Tambah Akun dengan Role Staff</h5>
    <p>
        Untuk menambah akun baru yang memiliki role staff, kirimkan permintaan ke <span class="badge bg-dark">/sso/staff/add</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span> dan kirimkan payload dalam body dalam format JSON sebagai berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "nama": "Karyawan Test",
    "email": "karyawan.test@simak.dev",
    "password": "password_awalan",
    "no_hp": "088xxxx",
    "is_akademik": true
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Staff - Edit Akun Staff</h5>
    <p>
        Untuk mengubah atau memperbarui data akun milik pengguna, kirimkan permintaan ke <span class="badge bg-dark">/sso/staff/update</span> dengan menggunakan HTTP method <span class="badge bg-info">put</span>. Sertakan payload seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "staff_id": 9,
    "nama": "Karyawan Test Edit",
    "email": "karyawan.test@simak.dev",
    "no_hp": "088xxxxx",
    "is_akademik": false,
    "is_marketing": true
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Staff - Hapus Akun</h5>
    <p>
        Untuk menghapus akun staff yang telah ada, kirimkan permintaan ke <span class="badge bg-dark">/sso/staff/delete</span> dengan menggunakan HTTP method <span class="badge bg-info">delete</span>. Sertakan payload seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "staff_id": 9
}</code></pre>
</section>

{{-- * Start of Mahasiswa Account --}}
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Mahasiswa - Get Daftar Akun Mahasiswa</h5>
    <p>
        Untuk melihat daftar akun dengan role mahasiswa kirimkan permintaan ke <span class="badge bg-dark">/sso/mahasiswa/list</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil API akan memberikan response seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "total_mahasiswa": 2,
        "list_mahasiswa": [
            {
                "id": 12,
                "kd_user": "MHS-1220281",
                "email": "1220281@simak.dev",
                "image": "student.png",
                "is_mhs": true,
                "is_dev": false,
                "is_doswal": false,
                "is_prodi": false,
                "is_admin": false,
                "is_dosen": false,
                "is_staff": false,
                "is_wk": false
            },
            {
                "id": 11,
                "kd_user": "MHS-3220285",
                "email": "3220285@simak.dev",
                "image": "student.png",
                "is_mhs": true,
                "is_dev": false,
                "is_doswal": false,
                "is_prodi": false,
                "is_admin": false,
                "is_dosen": false,
                "is_staff": false,
                "is_wk": false
            }
        ]
    }
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Mahasiswa - Get Detail Akun</h5>
    <p>
        Untuk melihat detail dari satu akun mahasiswa kirimkan permintaan ke <span class="badge bg-dark">/sso/mahasiswa/detail?user_id=12</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span> dan sesuaikan nilai query parameter <span class="badge bg-secondary">user_id</span> dengan nilai <b>user_id</b> yang ada saat get daftar akun mahasiswa. Contohnya, jika berhasil API akan memberikan response seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "mahasiswa": {
            "id": 12,
            "kd_user": "MHS-1220281",
            "email": "1220281@simak.dev",
            "image": "student.png",
            "is_mhs": true,
            "is_dev": false,
            "is_doswal": false,
            "is_prodi": false,
            "is_admin": false,
            "is_dosen": false,
            "is_staff": false,
            "is_wk": false
        },
        "site_access": [
            {
                "user_id": 12,
                "site_id": 4,
                "email": "1220281@simak.dev",
                "url": "http://stmikbdg-deteksi.test/",
                "name": "Sistem Deteksi Kemiripan Proposal Skripsi"
            }
        ]
    }
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Mahasiswa - Tambah Akun</h5>
    <p>
        Untuk menambah akun baru dengan role mahasiswa kirimkan permintaan ke <span class="badge bg-dark">/sso/mahasiswa/add</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span> dan sertakan payload dalam format JSON seperti di bawah ini.
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "kd_user": "1220313",
    "email": "1220313@simak.dev",
    "password": "password_awalan"
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Mahasiswa - Edit Akun</h5>
    <p>
        Untuk mengubah atau memperbarui data akun mahasiswa, kirimkan permintaan ke <span class="badge bg-dark">/sso/mahasiswa/update</span> dengan menggunakan HTTP method <span class="badge bg-info">put</span> dan sertakan payload seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "user_id": 116,
    "kd_user": "1220313",
    "email": "1220313.edit@simak.dev"
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Mahasiswa - Hapus Akun</h5>
    <p>
        Untuk menghapus akun dengan role mahasiswa kirimkan permintaan ke <span class="badge bg-dark">/sso/mahasiswa/delete</span> dengan menggunakan HTTP method <span class="badge bg-info">delete</span> dan sertakan nilai <b>user_id</b> sebagai payload dalam bentuk JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "user_id": 116
}</code></pre>
</section>
{{-- * End of Mahasiswa Account --}}

{{-- * Start of Dosen Account --}}
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Dosen - Get Daftar Akun</h5>
    <p>
        Untuk mendapat list atau daftar akun dengan role dosen kirimkan permintaan ke <span class="badge bg-dark">/sso/dosen/list</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil API akan memberikan response seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "total_dosen": 1,
        "list_dosen": [
            {
                "id": 1,
                "kd_user": "DSN-IF054",
                "email": "mina@simak.dev",
                "image": "dosen.png",
                "is_mhs": false,
                "is_dev": false,
                "is_doswal": true,
                "is_prodi": true,
                "is_admin": false,
                "is_dosen": true,
                "is_staff": true,
                "is_wk": false
            }
        ]
    }
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Dosen - Get Detail AKun</h5>
    <p>
        Untuk melihat detail dari satu akun dengan role dosen, kirimkan permintaan ke <span class="badge bg-dark">/sso/dosen/detail?user_id=1</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span> dan sesuaikan nilai query <span class="badge bg-secondary">user_id</span> dengan nilai <b>user_id</b> yang ada pada get list akun dosen. Jika berhasil API akan memberikan response seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "dosen": {
            "id": 1,
            "kd_user": "DSN-IF054",
            "email": "mina@simak.dev",
            "image": "dosen.png",
            "is_mhs": false,
            "is_dev": false,
            "is_doswal": true,
            "is_prodi": true,
            "is_admin": false,
            "is_dosen": true,
            "is_staff": true,
            "is_wk": false,
            "is_akademik": true,
            "is_marketing": false,
            "is_baak": false
        },
        "site_access": [
            {
                "user_id": 1,
                "site_id": 9,
                "email": "mina@simak.dev",
                "url": "http://stmikbdg-surat.test/",
                "name": "Sistem Surat Masuk dan Keluar"
            }
        ]
    }
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Dosen - Tambah Akun</h5>
    <p>
        Untuk menambah akun baru dengan role dosen, kirimkan permintaan ke <span class="badge bg-dark">/sso/dosen/add</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span> dan sertakan payload dalam format JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "kd_user": "IF043",
    "email": "dani.pradana@simak.dev",
    "password": "password",
    "is_dosen": true,
    "is_doswal": true,
    "is_staff": true,
    "is_akademik": true,
    "is_marketing": false,
    "is_baak": false,
    "is_secretary": false
}</code></pre>
    <p>
        Pastikan bahwa dosen yang akan dibuat akunnya telah terdapat pada database server STMIK Bandung karena diperlukan nilai <b>kd_user</b> yang valid yang berisi kode dosen.
    </p>
    <p>
        Kemudian, jika nilai <b>is_staff</b> adalah true, maka nilai is_akademik, is_marketing, is_baak, dan is_secretary harus diisi.
    </p>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Dosen - Edit Akun</h5>
    <p>
        Untuk mengubah atau memperbarui data akun dosen, kirimkan permintaan ke <span class="badge bg-dark">/sso/dosen/update</span> dengan menggunakan HTTP method <span class="badge bg-info">put</span> dan sertakan payload dalam format JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "user_id": 120,
    "kd_user": "IF043",
    "email": "dani.test@simak.dev",
    "password": "password",
    "is_dosen": true,
    "is_doswal": true,
    "is_prodi": false,
    "is_staff": true,
    "is_wk": false,
    "is_akademik": true,
    "is_marketing": false,
    "is_baak": false,
    "is_secretary": false
}</code></pre>
    <p>
        Kemudian, jika nilai <b>is_staff</b> adalah true, maka nilai is_akademik, is_marketing, is_baak, dan is_secretary harus diisi.
    </p>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Dosen - Hapus Akun</h5>
    <p>
        Untuk menghapus akun dengan role dosen, kirimkan permintaan ke <span class="badge bg-dark">/sso/dosen/delete</span> dengan menggunakan HTTP method <span class="badge bg-info">delete</span> dan sertakan payload dalam format JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "user_id": 120
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Admin - Get Daftar Akun</h5>
    <p>
        Untuk melihat daftar akun dengan role admin, kirimkan permintaan ke <span class="badge bg-dark">/sso/admin/list</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil API akan memberikan response seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_admin": [
            {
                "id": 31,
                "kd_user": "ADM-ADMDKP",
                "email": "admin.deteksi@simak.dev",
                "image": "admin.png",
                "is_mhs": false,
                "is_dev": false,
                "is_doswal": false,
                "is_prodi": false,
                "is_admin": true,
                "is_dosen": false,
                "is_staff": false,
                "is_wk": false
            },
            {
                "id": 27,
                "kd_user": "ADM-ADMSMK",
                "email": "admin.surat@simak.dev",
                "image": "admin.png",
                "is_mhs": false,
                "is_dev": false,
                "is_doswal": false,
                "is_prodi": false,
                "is_admin": true,
                "is_dosen": false,
                "is_staff": false,
                "is_wk": false
            }
        ]
    }
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Admin - Get Detail Akun</h5>
    <p>
        Untuk melihat detail dari satu akun dengan role admin, kirimkan permintaan ke <span class="badge bg-dark">/sso/admin/detail?user_id=31</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span> dan sesuaikan nilai <b>user_id</b> dengan nilai user_id yang ada saat get list akun admin. Jika berhasil API akan memberikan response seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "dosen": {
            "id": 31,
            "kd_user": "ADM-ADMDKP",
            "email": "admin.deteksi@simak.dev",
            "image": "admin.png",
            "is_mhs": false,
            "is_dev": false,
            "is_doswal": false,
            "is_prodi": false,
            "is_admin": true,
            "is_dosen": false,
            "is_staff": false,
            "is_wk": false
        },
        "site_access": [
            {
                "user_id": 31,
                "site_id": 4,
                "email": "admin.deteksi@simak.dev",
                "url": "http://stmikbdg-deteksi.test/",
                "name": "Sistem Deteksi Kemiripan Proposal Skripsi"
            }
        ]
    }
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Admin - Tambah Akun</h5>
    <p>
        Untuk menambahkan akun baru dengan role admin, kirimkan permintaan ke <span class="badge bg-dark">/sso/admin/add</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span> dan sertakan payload dalam format JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "kd_user": "LCL",
    "site_id": 3,
    "email": "admin.local@simak.dev",
    "password": "password"
}</code></pre>
    <p>
        Pastikan nilai <b>site_id</b> tersedia, nilai tersebut dapat dilihat dari API get list site tersedia yang telah terintegrasi dengan API dan SSO.
    </p>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Admin - Edit Akun</h5>
    <p>
        Untuk mengubah data akun admin, kirimkan permintaan ke <span class="badge bg-dark">/sso/admin/update</span> dengan menggunakan HTTP method <span class="badge bg-info">put</span> dan sertakan payload dalam format JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "user_id": 123,
    "site_id": 11,
    "kd_user": "KPS",
    "email": "admin.deteksi@simak.dev"
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Admin - Hapus Akun</h5>
    <p>
        Untuk menghapus akun dengan role admin, kirimkan permintaan ke <span class="badge bg-dark">/sso/admin/delete</span> dengan menggunakan HTTP method <span class="badge bg-info">delete</span>. Sertakan nilai <b>user_id</b> dalam format JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "user_id": 122
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Admin - Get Daftar Sistem Informasi Berbasis Web</h5>
    <p>
        Untuk melihat daftar sistem informasi web yang digunakan saat menambah akun dengan role admin, kirimkan permintaan ke <span class="badge bg-dark">/sso/admin/sites/list</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil API akan memberikan response:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "list_sites": [
            {
                "id": 9,
                "name": "Sistem Surat Masuk dan Keluar",
                "url": "http://stmikbdg-surat.test/"
            },
            {
                "id": 8,
                "name": "Dokumentasi REST API",
                "url": "http://stmikbdg-api.test/"
            }
        ]
    }
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Site Control - Get Site List Berdasarkan Role</h5>
    <p>
        Untuk mendapatkan daftar sistem informasi berbasis web yang akan digunakan pada halaman pengontrolan akses user ke sistem, kirimkan permintaan ke <span class="badge bg-dark">/sso/sites/list</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span> untuk melihat semua sistem informasi berbasiswa web yang telah tersedia. Hasilnya seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "sites": [
            {
                "id": 9,
                "url": "http://stmikbdg-surat.test/",
                "name": "Sistem Surat Masuk dan Keluar",
                "is_mhs": false,
                "is_dev": false,
                "is_doswal": false,
                "is_prodi": false,
                "is_dosen": false,
                "is_admin": true,
                "is_staff": true,
                "is_wk": true,
                "is_secretary": true
            },
            {
                "id": 8,
                "url": "http://stmikbdg-api.test/",
                "name": "Dokumentasi REST API",
                "is_mhs": false,
                "is_dev": true,
                "is_doswal": false,
                "is_prodi": false,
                "is_dosen": false,
                "is_admin": false,
                "is_staff": false,
                "is_wk": false,
                "is_secretary": false
            }
        ]
    }
}</code></pre>
    <p>
        Jika ingin melihat sistem informasi yang ditujukan untuk role tertentu, kirimkan permintaan ke URL yang sama, tetapi tambahkan query parameter <span class="badge bg-secondary">site_role</span> dan isi dengan nilai dev, mhs, adm, stf, atau dosen. Contohnya menjadi <span class="badge bg-dark">/sso/sites/list?site_role=dev</span> untuk melihat sistem informasi yang bisa tersedia untuk developer. Hasilnya seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "sites": [
            {
                "id": 8,
                "url": "http://stmikbdg-api.test/",
                "name": "Dokumentasi REST API"
            }
        ]
    }
}</code></pre>
    <p>
        Jika ingin melihat daftar user yang memiliki hak akses ke sistem tertentu kirimkan juga permintaan ke URL yang sama, tetapi menggunakan query parameter <span class="badge bg-secondary">site_id</span>. Contohnya <span class="badge bg-dark">/sso/sites/list?site_id=3</span>. Hasilnya seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "site_detail": {
            "id": 3,
            "url": "http://stmikbdg-kuesioner.test/",
            "name": "Sistem Kuesioner",
            "is_mhs": true,
            "is_dev": false,
            "is_doswal": false,
            "is_prodi": false,
            "is_dosen": false,
            "is_admin": true,
            "is_staff": false,
            "is_wk": false,
            "is_secretary": false
        },
        "site_users": [
            {
                "user_id": 24,
                "site_id": 3,
                "email": "admin.kuesioner@simak.dev",
                "url": "http://stmikbdg-kuesioner.test/",
                "name": "Sistem Kuesioner"
            },
            {
                "user_id": 2,
                "site_id": 3,
                "email": "1220001@simak.dev",
                "url": "http://stmikbdg-kuesioner.test/",
                "name": "Sistem Kuesioner"
            },
            {
                "user_id": 11,
                "site_id": 3,
                "email": "3220285@simak.dev",
                "url": "http://stmikbdg-kuesioner.test/",
                "name": "Sistem Kuesioner"
            }
        ]
    }
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Site Control - Menambah Akses User</h5>
    <p>
        Untuk menambah akses user ke sistem informasi, kirimkan permintaan ke <span class="badge bg-dark">/sso/sites/user-access</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span> dan sertakan payload dalam format JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "user_id": 123,
    "site_id": 3
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Site Control - Menambah Sistem Informasi</h5>
    <p>
        Untuk menambahkan sistem informasi baru, kirimkan permintaan ke <span class="badge bg-dark">/sso/sites/add</span> dengan menggunakan HTTP method <span class="badge bg-info">post</span> dan sertakan payload dalam bentuk JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "url": "http://localhost:8080/",
    "name": "SI Test",
    "is_mhs": true,
    "is_admin": true,
    "is_dosen": false,
    "is_doswal": false,
    "is_prodi": false,
    "is_dev": true
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Site Control - Menghapus Akses User ke Sistem Informasi</h5>
    <p>
        Untuk menghapus akses user ke suatu sistem informasi, kirimkan permintaan ke <span class="badge bg-dark">/sso/sites/user-access</span> dengan menggunakan HTTP method <span class="badge bg-info">delete</span> dan sertakan payload dalam format JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "site_id": 8,
    "user_id": 12
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Developer - Get All Users</h5>
    <p>
        Untuk mendapatkan semua daftar user yang akan digunakan saat akan menambahkan role developer, kirimkan permintaan ke <span class="badge bg-dark">/sso/users/list</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil API akan memberikan response seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "users": [
            {
                "id": 29,
                "email": "muji@stmik-bandung.ac.id"
            },
            {
                "id": 28,
                "email": "linda@stmik-bandung.ac.id"
            }
        ]
    }
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Developer - Get All Developer Users</h5>
    <p>
        Untuk mendapatkan daftar user yang sudah memiliki role developer, kirimkan permintaan ke <span class="badge bg-dark">/sso/developer/list</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil API akan memberikan response seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "users": [
            {
                "id": 2,
                "email": "1220001@stmik-bandung.ac.id"
            }
        ]
    }
}</code></pre>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Developer - Give Access</h5>
    <p>
        Untuk memberikan akses sebagai developer ke suatu user, kirimkan permintaan ke <span class="badge bg-dark">/sso/developer/access</span> dengan menggunakan HTTP method <span class="badge bg-info">put</span> dan sertakan payload dalam format JSON seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "user_id": 12,
    "site_id": 8
}</code></pre>
    <p>
        Nilai <b>site_id</b> hanya dapat menggunakan site atau sistem informasi yang telah memiliki role developer.
    </p>
</section>
<section class="mt-5">
    <h5 class="mb-3 fw-bold">(ADM) Get Total Users</h5>
    <p>
        Untuk mendapatkan total user mahasiswa, dosen, staff, dan admin, kirimkan permintaan ke <span class="badge bg-dark">/sso/users/statistik</span> dengan menggunakan HTTP method <span class="badge bg-info">get</span>. Jika berhasil API akan memberikan response seperti berikut:
    </p>
    <pre><code class="language-json bg-primary-subtle">{
    "status": "success",
    "data": {
        "total_akun_mahasiswa": 5,
        "total_akun_dosen": 4,
        "total_akun_staff": 6,
        "total_akun_admin": 5
    }
}</code></pre>
</section>
