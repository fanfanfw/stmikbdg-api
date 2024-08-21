<?php

namespace App\Http\Controllers\Docs;

use App\Http\Controllers\Controller;

class DocsController extends Controller
{
    public function home() {
        return view('docs.contents.home.index', [
            'title' => 'Home',
        ]);
    }

    public function homeTabs($tabName) {
        switch (strtolower($tabName)) {
            case 'update':
                $content = view('docs.contents.home.update')->render();
                break;
            case 'penggunaan':
                $content = view('docs.contents.home.penggunaan')->render();
                break;
            case 'lainnya':
                $content = view('docs.contents.home.lainnya')->render();
                break;
            case 'upcoming':
                $content = view('docs.contents.home.upcoming')->render();
                break;
            default:
                $content = 'Tab not found.';
                break;
        }

        return response()->json([
            'content' => $content,
        ]);
    }

    public function login() {
        return view('login');
    }

    public function authentications() {
        return view('docs.contents.auth', [
            'title' => 'Authentications',
        ]);
    }

    public function users() {
        return view('docs.contents.users', [
            'title' => 'Users',
        ]);
    }

    public function androidPengumuman() {
        return view('docs.contents.android.pengumuman.index', [
            'title' => 'KRS'
        ]);
    }

    public function androidPengumumanTabs($tabName) {
        switch (strtolower($tabName)) {
            case 'admin':
                $content = view('docs.contents.android.pengumuman.admin')->render();
                break;
            case 'mahasiswa':
                $content = view('docs.contents.android.pengumuman.mahasiswa')->render();
                break;
            case 'dosen':
                $content = view('docs.contents.android.pengumuman.dosen')->render();
                break;
            default:
                $content = 'Tab not found.';
                break;
        }

        return response()->json([
            'content' => $content,
        ]);
    }

    public function androidKrs() {
        return view('docs.contents.android.krs.index', [
            'title' => 'KRS'
        ]);
    }

    public function androidKrsTabs($tabName) {
        switch (strtolower($tabName)) {
            case 'doswal':
                $content = view('docs.contents.android.krs.doswal')->render();
                break;
            case 'mahasiswa':
                $content = view('docs.contents.android.krs.mahasiswa')->render();
                break;
            default:
                $content = 'Tab not found.';
                break;
        }

        return response()->json([
            'content' => $content,
        ]);
    }

    public function androidKelasKuliah() {
        return view('docs.contents.android.kelas-kuliah.index', [
            'title' => 'KRS'
        ]);
    }

    public function androidKelasKuliahTabs($tabName) {
        switch (strtolower($tabName)) {
            case 'dosen':
                $content = view('docs.contents.android.kelas-kuliah.dosen')->render();
                break;
            case 'mahasiswa':
                $content = view('docs.contents.android.kelas-kuliah.mahasiswa')->render();
                break;
            default:
                $content = 'Tab not found.';
                break;
        }

        return response()->json([
            'content' => $content,
        ]);
    }

    public function sikps() {
        return view('docs.contents.deteksi-proposal.index', [
            'title' => 'SIKPS - Deteksi Proposal'
        ]);
    }

    public function sikpsTabs($tabName) {
        switch (strtolower($tabName)) {
            case 'admin':
                $content = view('docs.contents.deteksi-proposal.admin')->render();
                break;
            case 'mahasiswa':
                $content = view('docs.contents.deteksi-proposal.mahasiswa')->render();
                break;
            default:
                $content = 'Tab not found.';
                break;
        }

        return response()->json([
            'content' => $content,
        ]);
    }

    public function kuesioner() {
        return view('docs.contents.kuesioner.index', [
            'title' => 'Kuesioner'
        ]);
    }

    public function kuesionerTabs($tabName) {
        switch (strtolower($tabName)) {
            case 'admin':
                $content = view('docs.contents.kuesioner.admin')->render();
                break;
            case 'mahasiswa':
                $content = view('docs.contents.kuesioner.mahasiswa')->render();
                break;
            default:
                $content = 'Tab not found.';
                break;
        }

        return response()->json([
            'content' => $content,
        ]);
    }

    public function acl() {
        return view('docs.contents.acl', [
            'title' => 'ACL',
        ]);
    }

    public function kamus() {
        return view('docs.contents.kamus', [
            'title' => 'Kamus'
        ]);
    }

    public function additionalRoutes() {
        return view('docs.contents.additional', [
            'title' => 'Additional Routes'
        ]);
    }

    public function marketing() {
        return view('docs.contents.marketing', [
            'title' => 'Sistem Marketing'
        ]);
    }

    public function surat() {
        return view('docs.contents.surat', [
            'title' => 'Sistem Penomoran Surat'
        ]);
    }

    public function beritaAcara() {
        return view('docs.contents.berita', [
            'title' => 'Sistem Berita Acara'
        ]);
    }

    public function pengajuanWisuda() {
        return view('docs.contents.pengajuan-wisuda.index', [
            'title' => 'Pengajuan Wisuda'
        ]);
    }

    public function pengajuanWisudaTabs($tabName) {
        switch (strtolower($tabName)) {
            case 'admin':
                $content = view('docs.contents.pengajuan-wisuda.admin')->render();
                break;
            case 'mahasiswa':
                $content = view('docs.contents.pengajuan-wisuda.mahasiswa')->render();
                break;
            default:
                $content = 'Tab not found.';
                break;
        }

        return response()->json([
            'content' => $content,
        ]);
    }

    public function antrian() {
        return view('docs.contents.antrian.index', [
            'title' => 'Antrian'
        ]);
    }

    public function antrianTabs($tabName) {
        switch (strtolower($tabName)) {
            case 'dosen':
                $content = view('docs.contents.antrian.dosen')->render();
                break;
            case 'bimbingan':
                $content = view('docs.contents.antrian.bimbingan')->render();
                break;
            case 'sidang':
                $content = view('docs.contents.antrian.sidang')->render();
                break;
            case 'tamu':
                $content = view('docs.contents.antrian.tamu')->render();
                break;
            case 'public':
                $content = view('docs.contents.antrian.public')->render();
                break;
            default:
                $content = 'Tab not found.';
                break;
        }

        return response()->json([
            'content' => $content,
        ]);
    }
}
