@extends('layouts.app')
@section('title', 'Stream Cipher')

@section('content')
<style>
    .panel { background:#0d2142; border:1px solid #16315c; border-radius:12px; padding:20px; }
    .panel label { font-weight:600; margin-bottom:6px; }
    .form-control, .form-select { background:#081a38; border:1px solid #16315c; color:#e6edf7; }
    .form-control:focus, .form-select:focus { background:#081a38; color:#fff; border-color:#2f7bff; box-shadow:none; }
    .form-control::placeholder { color:#6f86ab; }
    .btn-enc { background:#1f6bff; border:1px solid #1f6bff; color:#fff; }
    .btn-dec { background:transparent; border:1px solid #2f7bff; color:#e6edf7; }
    .info-title { color:#e6edf7; font-weight:600; }
    .info-text { color:#9fb2d1; font-size:.9rem; }
    .back-btn { display:inline-block; background:#0d2142; border:1px solid #16315c; color:#e6edf7;
                padding:3px 12px; border-radius:6px; text-decoration:none; font-size:.85rem; margin-bottom:14px; }
</style>

<a href="/" class="back-btn"><i class="bi bi-arrow-left"></i> Kembali</a>

<div class="d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-shuffle" style="font-size:2.6rem;color:#2f7bff"></i>
    <div>
        <h2 class="fw-bold mb-0">Stream Cipher</h2>
        <div class="info-text">Metode kriptografi yang mengenkripsi data secara bertahap menggunakan aliran kunci (keystream).</div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="panel">
            <div class="row g-3">
                <div class="col-12">
                    <label>Pilih Algoritma</label>
                    <select class="form-select" id="algo">
                        <option value="chacha20">ChaCha20</option>
                        <option value="rc4">RC4</option>
                        <option value="salsa20">Salsa20</option>
                        <option value="a51">A5/1</option>
                        <option value="trivium">Trivium</option>
                        <option value="grain">Grain</option>
                        <option value="spritz">Spritz</option>
                    </select>
                </div>
                <div class="col-12">
                    <label>Pesan</label>
                    <textarea class="form-control" id="pesan" rows="4" placeholder="Masukkan pesan..."></textarea>
                </div>
                <div class="col-12">
                    <label>Kunci</label>
                    <input type="text" class="form-control" id="kunci" placeholder="Masukkan kunci...">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-enc" id="btnEnkripsi"><i class="bi bi-play-fill"></i> Enkripsi</button>
                    <button class="btn btn-dec" id="btnDekripsi"><i class="bi bi-arrow-repeat"></i> Dekripsi</button>
                </div>
            </div>
        </div>

        <div class="panel mt-3">
            <div class="d-flex justify-content-between align-items-center">
                <label class="mb-0"><i class="bi bi-check-circle"></i> Hasil</label>
                <button class="btn btn-sm btn-dec" id="btnSalin">Salin</button>
            </div>
            <div class="info-text mt-2" id="hasil">Hasil akan muncul di sini...</div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="panel">
            <div class="info-title mb-2"><i class="bi bi-info-circle-fill" style="color:#2f7bff"></i> Informasi</div>
            <div class="info-title" id="infoNama"></div>
            <div class="info-text mb-3" id="infoDeskripsi"></div>
            <div class="info-title">Kelebihan:</div>
            <div class="info-text mb-3" id="infoKelebihan"></div>
            <div class="info-title">Kekurangan:</div>
            <div class="info-text" id="infoKekurangan"></div>
        </div>
    </div>
</div>

<script>
    const infoAlgo = {
        chacha20: {
            nama: 'ChaCha20',
            deskripsi: 'Stream cipher modern rancangan Daniel J. Bernstein, turunan dari Salsa20. Di aplikasi ini memakai varian XChaCha20 dengan nonce acak 24 byte.',
            kelebihan: '- Cepat tanpa perangkat keras khusus<br>- Keamanan kuat<br>- Dipakai di TLS dan WireGuard',
            kekurangan: '- Nonce tidak boleh dipakai ulang dengan kunci yang sama'
        },
        rc4: {
            nama: 'RC4',
            deskripsi: 'Stream cipher klasik rancangan Ron Rivest (1987). Terdiri dari tahap KSA (penjadwalan kunci) dan PRGA (pembangkit keystream).',
            kelebihan: '- Sangat sederhana<br>- Cepat<br>- Panjang kunci fleksibel (1-256 byte)',
            kekurangan: '- Keystream memiliki bias<br>- Sudah dianggap tidak aman untuk sistem modern'
        },
        salsa20: {
            nama: 'Salsa20',
            deskripsi: 'Stream cipher rancangan Daniel J. Bernstein (2005), finalis proyek eSTREAM. Memakai 20 ronde operasi ADD-ROTATE-XOR pada state 16 kata 32-bit, kunci 256-bit dan nonce 64-bit.',
            kelebihan: '- Cepat dan mudah diimplementasikan<br>- Tahan serangan timing karena tanpa tabel S-box<br>- Desain sederhana dan terbuka',
            kekurangan: '- Nonce hanya 64-bit sehingga tidak boleh dipakai ulang<br>- Tidak punya autentikasi bawaan'
        },
        a51: {
            nama: 'A5/1',
            deskripsi: 'Stream cipher yang dipakai pada jaringan seluler GSM. Terdiri dari tiga LFSR (19, 22, dan 23 bit) yang digeser dengan aturan clocking mayoritas. Kunci 64-bit dan frame number 22-bit.',
            kelebihan: '- Sangat efisien dibuat dalam perangkat keras<br>- Struktur sederhana dan cocok untuk belajar LFSR',
            kekurangan: '- Kunci efektif pendek sehingga sudah berhasil dibobol<br>- Tidak direkomendasikan untuk keamanan nyata'
        },
        trivium: {
            nama: 'Trivium',
            deskripsi: 'Stream cipher finalis proyek eSTREAM (portofolio perangkat keras) rancangan De Cannière dan Preneel. State 288 bit dalam tiga register geser, kunci 80-bit dan IV 80-bit, dengan 1152 ronde pemanasan sebelum keystream keluar.',
            kelebihan: '- Sangat ringan dan cepat di perangkat keras<br>- Desain sangat sederhana (hanya AND dan XOR)<br>- Belum ada serangan praktis terhadap versi penuh',
            kekurangan: '- Kunci hanya 80 bit<br>- IV tidak boleh dipakai ulang dengan kunci yang sama'
        },
        grain: {
            nama: 'Grain (v1)',
            deskripsi: 'Stream cipher finalis eSTREAM untuk perangkat keras terbatas. Menggabungkan LFSR 80-bit, NFSR 80-bit, dan fungsi output non-linear h(x). Kunci 80-bit dan IV 64-bit, dengan 160 clock inisialisasi.',
            kelebihan: '- Memakai area perangkat keras yang kecil<br>- Kombinasi LFSR dan NFSR menambah kompleksitas<br>- Cocok dipakai untuk belajar register geser',
            kekurangan: '- Kunci hanya 80 bit<br>- IV tidak boleh dipakai ulang dengan kunci yang sama'
        },
        spritz: {
            nama: 'Spritz',
            deskripsi: 'Stream cipher rancangan Rivest dan Schuldt (2014) sebagai pengganti RC4. Menambah variabel w dan z, serta operasi whip, crush, dan shuffle pada state permutasi 256 byte dengan metode sponge (absorb dan squeeze).',
            kelebihan: '- Memperbaiki bias keystream pada RC4<br>- Mudah dipahami karena masih berbasis permutasi<br>- Panjang kunci fleksibel',
            kekurangan: '- Lebih lambat dari RC4<br>- Kurang banyak dianalisis dibanding cipher modern'
        }
    };

    const tampilkanInfo = () => {
        const i = infoAlgo[document.getElementById('algo').value];
        document.getElementById('infoNama').textContent = i.nama;
        document.getElementById('infoDeskripsi').textContent = i.deskripsi;
        document.getElementById('infoKelebihan').innerHTML = i.kelebihan;
        document.getElementById('infoKekurangan').innerHTML = i.kekurangan;
    };
    document.getElementById('algo').onchange = tampilkanInfo;
    tampilkanInfo();

    const kirim = async (aksi) => {
        const hasil = document.getElementById('hasil');
        const res = await fetch('/stream-cipher/proses', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                aksi: aksi,
                algo: document.getElementById('algo').value,
                pesan: document.getElementById('pesan').value,
                kunci: document.getElementById('kunci').value
            })
        });
        const data = await res.json();
        hasil.style.color = data.error ? '#ff7b7b' : '#e6edf7';
        hasil.style.wordBreak = 'break-all';
        hasil.textContent = data.error ?? data.hasil ?? data.message ?? JSON.stringify(data);
    };

    document.getElementById('btnEnkripsi').onclick = () => kirim('enkripsi');
    document.getElementById('btnDekripsi').onclick = () => kirim('dekripsi');
    document.getElementById('btnSalin').onclick = () => {
        navigator.clipboard.writeText(document.getElementById('hasil').textContent);
    };
</script>
@endsection