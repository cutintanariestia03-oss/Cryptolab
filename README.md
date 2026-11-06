# CryptoLab

Aplikasi web pembelajaran kriptografi untuk UTS Project (20 Oktober – 10 November 2026).
Dibangun dengan Laravel dan mengimplementasikan algoritma **Stream Cipher**.

## Anggota Kelompok

| Nama | 
| Zatalini|
| Maulida Hidayani| 
| Cut Intan Ariestia| 
| Devia Raisa| 
| Nasyatha Syakira| 

## Algoritma Stream Cipher

- [x] RC4
- [x] ChaCha20
- [x] Salsa20
- [x] A5/1
- [ ] [Algoritma 5]
- [ ] [Algoritma 6]
- [ ] [Algoritma 7]

## Kebutuhan Sistem

- PHP 8.2 atau lebih baru
- Composer
- Node.js dan NPM

## Cara Menjalankan

1. Clone repository

        git clone 
        cd Cryptolab

2. Install dependensi

        composer install
        npm install

3. Siapkan environment

        cp .env.example .env
        php artisan key:generate

4. Jalankan aplikasi (gunakan dua terminal)

        npm run dev
        php artisan serve

5. Buka `http://127.0.0.1:8000` di browser.

## Cara Penggunaan

1. Pilih algoritma pada menu.
2. Masukkan teks (plaintext atau ciphertext) dan kunci.
3. Klik **Enkripsi** atau **Dekripsi**.
4. Hasil tampil di bagian bawah halaman.

## Lisensi

Proyek ini dibuat untuk keperluan akademik.