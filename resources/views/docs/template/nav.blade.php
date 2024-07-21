<nav class="navbar fixed-top navbar-expand-lg bg-primary px-3 navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/">
        <div class="navbar-brand-wrapper d-flex align-items-center gap-2">
            <div class="navbar-img-wrapper">
                <img src="/images/stmikbdg_logo.png" alt="Logo" width="75" height="auto" class="d-inline-block align-text-top">
            </div>
            <div class="title-wrapper">
                    <span class="fw-bold fs-5">STMIK Bandung</span>
                <div style="font-size: 12px;">
                    API Documentation
                </div>
            </div>
        </div>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarText" aria-controls="navbarText" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse ms-4" id="navbarText">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
                <a class="nav-link text-white" href="/docs/api/authentications">Auth</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="/docs/api/users">Users</a>
            </li>

            {{-- Menu untuk android --}}
            <li class="nav-item dropdown">
                <a class="nav-link text-white dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Android</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/docs/api/android/krs">KRS</a></li>
                    <li><a class="dropdown-item" href="/docs/api/android/pengumuman">Pengumuman</a></li>
                    <li><a class="dropdown-item" href="/docs/api/kelas-mahasiswa">KELAS - MHS</a></li>
                    <li><a class="dropdown-item" href="/docs/api/kelas-dosen">KELAS - DSN</a></li>
                </ul>
            </li>

            {{-- Menu untuk web --}}
            <li class="nav-item dropdown">
                <a class="nav-link text-white dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Web</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/docs/api/sikps">SIKPS</a></li>
                    <li><a class="dropdown-item" href="/docs/api/antrian">Antrian</a></li>
                    <li><a class="dropdown-item" href="/docs/api/kuesioner">Kuesioner</a></li>
                    <li><a class="dropdown-item" href="/docs/api/marketing">Marketing</a></li>
                    {{-- <li><a class="dropdown-item" href="/docs/api/berita-acara">Berita Acara</a></li> --}}
                    <li><a class="dropdown-item" href="/docs/api/pengajuan-wisuda">Pengajuan Wisuda</a></li>
                    <li><a class="dropdown-item" href="/docs/api/surat">Surat Masuk dan Keluar</a></li>
                    <hr>
                    <li><a class="dropdown-item" href="/docs/api/acl">Access Control Level</a></li>
                </ul>
            </li>

            {{-- Menu lainnya--}}
            <li class="nav-item dropdown">
                <a class="nav-link text-white dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Lainnya</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/docs/api/kamus">Kamus</a></li>
                    <li><a class="dropdown-item" href="/docs/api/additional">Routes Tambahan</a></li>
                </ul>
            </li>
        </ul>

        {{-- Logout --}}
        <form class="d-flex" action="/docs/api/logout">
            <button class="btn btn-sm btn-outline-light" >
                <i data-feather="log-out" style="width: 1.3em"></i>
                <span>
                    Logout
                </span>
            </button>
        </form>
    </div>
  </div>
</nav>
