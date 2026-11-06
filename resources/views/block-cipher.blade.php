@extends('layouts.app')
@section('title', 'Block Cipher')

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
    #hasil { white-space:pre-wrap; }
</style>

<a href="/" class="back-btn"><i class="bi bi-arrow-left"></i> Kembali</a>

<div class="d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-lock-fill" style="font-size:2.6rem;color:#2f7bff"></i>
    <div>
        <h2 class="fw-bold mb-0">Block Cipher</h2>
        <div class="info-text">Metode kriptografi yang mengenkripsi data dalam blok berukuran tetap.</div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="panel">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="algo">Pilih Algoritma</label>
                    <select class="form-select" id="algo">
                        <option value="aes">AES</option>
                        <option value="des">DES</option>
                        <option value="3des">3DES</option>
                        <option value="blowfish">Blowfish</option>
                        <option value="twofish">Twofish</option>
                        <option value="rc2">RC2</option>
                        <option value="camellia">Camellia</option>
                        <option value="aria">ARIA</option>
                        <option value="sm4">SM4</option>
                        <option value="rijndael">Rijndael-128</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="mode">Mode Operasi</label>
                    <select class="form-select" id="mode">
                        <option value="cbc">CBC</option>
                        <option value="ecb">ECB</option>
                        <option value="cfb">CFB</option>
                        <option value="cfb1">CFB1</option>
                        <option value="cfb8">CFB8</option>
                        <option value="ctr">CTR</option>
                        <option value="ofb">OFB</option>
                    </select>
                </div>
                <div class="col-12">
                    <label for="pesan">Pesan (Plaintext / Ciphertext Base64)</label>
                    <textarea class="form-control" id="pesan" rows="4" placeholder="Masukkan pesan..."></textarea>
                </div>
                <div class="col-12">
                    <label for="kunci">Kunci</label>
                    <input type="text" class="form-control" id="kunci" autocomplete="off" placeholder="Masukkan kunci...">
                    <div class="info-text mt-1" id="keyHelp"></div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="button" class="btn btn-enc" id="btnEnkripsi"><i class="bi bi-play-fill"></i> Enkripsi</button>
                    <button type="button" class="btn btn-dec" id="btnDekripsi"><i class="bi bi-arrow-repeat"></i> Dekripsi</button>
                </div>
            </div>
        </div>

        <div class="panel mt-3">
            <div class="d-flex justify-content-between align-items-center">
                <label class="mb-0"><i class="bi bi-check-circle"></i> Hasil</label>
                <button type="button" class="btn btn-sm btn-dec" id="btnSalin">Salin</button>
            </div>
            <div class="info-text mt-2" id="hasil" aria-live="polite">Hasil akan muncul di sini...</div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="panel">
            <div class="info-title mb-2"><i class="bi bi-info-circle-fill" style="color:#2f7bff"></i> Informasi</div>
            <div class="info-text mb-3" id="algoInfo"></div>
            <div class="info-title">Mode <span id="modeName">CBC</span></div>
            <div class="info-text" id="modeInfo"></div>
            <hr style="border-color:#24436e">
            
        </div>
    </div>
</div>

<script>
    const infoAlgoritma = {
        aes: {
            nama: 'AES',
            info: 'Advanced Encryption Standard. Algoritma block cipher modern yang banyak digunakan.',
            kunci: '16, 24, atau 32 byte (untuk teks ASCII biasanya 16, 24, atau 32 karakter).'
        },
        des: {
            nama: 'DES',
            info: 'Data Encryption Standard. Algoritma lama dengan ukuran kunci 8 byte; tidak disarankan untuk keamanan modern.',
            kunci: 'Tepat 8 byte.'
        },
        '3des': {
            nama: '3DES',
            info: 'Triple DES menerapkan operasi DES tiga kali. Disediakan untuk kompatibilitas dan pembelajaran; sudah dianggap usang.',
            kunci: '16 atau 24 byte.'
        },
        blowfish: {
            nama: 'Blowfish',
            info: 'Block cipher dengan panjang kunci variabel. Ukuran bloknya 64-bit.',
            kunci: '4 sampai 56 byte.'
        },
        twofish: {
            nama: 'Twofish',
            info: 'Block cipher dengan ukuran blok 128-bit dan beberapa pilihan panjang kunci.',
            kunci: '16, 24, atau 32 byte.'
        },
        rc2: {
            nama: 'RC2',
            info: 'Block cipher lama dengan panjang kunci variabel. Digunakan di sini untuk pembelajaran/kompatibilitas.',
            kunci: '1 sampai 128 byte.'
        },
        camellia: {
            nama: 'Camellia',
            info: 'Block cipher yang tersedia melalui OpenSSL jika build OpenSSL pada server mendukungnya.',
            kunci: '16, 24, atau 32 byte.'
        },
        aria: {
            nama: 'ARIA',
            info: 'Block cipher yang distandardisasi di Korea Selatan. Ketersediaannya bergantung pada OpenSSL server.',
            kunci: '16, 24, atau 32 byte.'
        },
        sm4: {
            nama: 'SM4',
            info: 'Block cipher standar Tiongkok. Ketersediaannya bergantung pada OpenSSL server.',
            kunci: 'Tepat 16 byte.'
        },
        rijndael: {
            nama: 'Rijndael-128',
            info: 'Keluarga cipher yang menjadi dasar AES. Varian ini memakai ukuran blok 128-bit; ukuran kunci dapat bervariasi.',
            kunci: '16, 20, 24, 28, atau 32 byte.'
        }
    };

    const algo = document.getElementById('algo');
    const mode = document.getElementById('mode');
    const kunci = document.getElementById('kunci');
    const hasil = document.getElementById('hasil');

    function perbaruiInfo() {
        const item = infoAlgoritma[algo.value];
        document.getElementById('algoInfo').textContent = item.info;
        document.getElementById('keyHelp').textContent = 'Aturan kunci ' + item.nama + ': ' + item.kunci;
        kunci.placeholder = 'Masukkan kunci: ' + item.kunci;
        document.getElementById('modeName').textContent = mode.value.toUpperCase();
        const modeDescriptions = {
            cbc: 'Cipher Block Chaining: menggunakan IV acak yang disimpan bersama ciphertext.',
            ecb: 'Electronic Codebook: tanpa IV; hanya untuk pembelajaran, tidak disarankan untuk data nyata.',
            cfb: 'Cipher Feedback: menggunakan IV dan memproses data seperti aliran.',
            cfb1: 'Cipher Feedback 1-bit: mode umpan balik per bit; tidak didukung semua cipher.',
            cfb8: 'Cipher Feedback 8-bit: mode umpan balik per byte; tidak didukung semua cipher.',
            ctr: 'Counter: menggunakan nonce/counter; dukungan bergantung pada cipher.',
            ofb: 'Output Feedback: menggunakan IV dan menghasilkan aliran kunci.'
        };
        document.getElementById('modeInfo').textContent = modeDescriptions[mode.value];
    }

    async function kirim(aksi) {
        hasil.style.color = '#e6edf7';
        hasil.textContent = 'Memproses...';

        try {
            const res = await fetch('/block-cipher/proses', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    aksi: aksi,
                    algo: algo.value,
                    mode: mode.value,
                    pesan: document.getElementById('pesan').value,
                    kunci: kunci.value
                })
            });

            const data = await res.json();
            if (!res.ok || data.error) {
                throw new Error(data.error || 'Permintaan gagal. Periksa input dan konfigurasi server.');
            }

            hasil.style.color = '#e6edf7';
            hasil.textContent = data.hasil ?? 'Tidak ada hasil dari server.';
        } catch (error) {
            hasil.style.color = '#ff7b7b';
            hasil.textContent = error.message || 'Terjadi kesalahan saat menghubungi server.';
        }
    }

    algo.addEventListener('change', perbaruiInfo);
    mode.addEventListener('change', perbaruiInfo);
    document.getElementById('btnEnkripsi').addEventListener('click', () => kirim('enkripsi'));
    document.getElementById('btnDekripsi').addEventListener('click', () => kirim('dekripsi'));
    document.getElementById('btnSalin').addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(hasil.textContent);
        } catch (error) {
            alert('Tidak dapat menyalin otomatis. Silakan salin hasil secara manual.');
        }
    });

    perbaruiInfo();
</script>
@endsection
