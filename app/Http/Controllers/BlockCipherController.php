<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use phpseclib3\Crypt\DES;
use phpseclib3\Crypt\TripleDES;
use phpseclib3\Crypt\Blowfish;
use phpseclib3\Crypt\Twofish;
use phpseclib3\Crypt\RC2;
use phpseclib3\Crypt\Rijndael;

class BlockCipherController extends Controller
{
    // Cipher yang disediakan oleh OpenSSL pada instalasi PHP tertentu.
    private array $openssl = [
        'camellia' => [16 => 'camellia-128', 24 => 'camellia-192', 32 => 'camellia-256'],
        'aria'     => [16 => 'aria-128', 24 => 'aria-192', 32 => 'aria-256'],
        'sm4'      => [16 => 'sm4'],
    ];

    // Daftar cipher phpseclib. Panjang kunci dihitung dalam byte.
    private function seclib(): array
    {
        return [
            '3des'     => [TripleDES::class, 8,  [16, 24], 'Kunci 3DES harus 16 atau 24 byte.'],
            'blowfish' => [Blowfish::class, 8, range(4, 56), 'Kunci Blowfish harus 4 sampai 56 byte.'],
            'twofish'  => [Twofish::class, 16, [16, 24, 32], 'Kunci Twofish harus 16, 24, atau 32 byte.'],
            'rc2'      => [RC2::class, 8, range(1, 128), 'Kunci RC2 harus 1 sampai 128 byte.'],
            // Rijndael-128 menggunakan ukuran blok 128-bit; berbeda dari varian Rijndael
            // dengan ukuran blok 160, 192, 224, atau 256-bit.
            'rijndael' => [Rijndael::class, 16, [16, 20, 24, 28, 32], 'Kunci Rijndael harus 16, 20, 24, 28, atau 32 byte.'],
        ];
    }

    public function proses(Request $request)
    {
        $request->validate([
            'aksi'  => 'required|in:enkripsi,dekripsi',
            'algo'  => 'required|string',
            'mode'  => 'required|in:cbc,ecb,cfb,cfb1,cfb8,ctr,ofb',
            'pesan' => 'nullable|string',
            'kunci' => 'required|string',
        ]);

        $algo = $request->input('algo');

        if ($algo === 'aes') {
            return $this->aes($request);
        }
        if ($algo === 'des') {
            return $this->des($request);
        }
        if (isset($this->openssl[$algo])) {
            return $this->viaOpenssl($request, $algo);
        }
        if (isset($this->seclib()[$algo])) {
            return $this->viaSeclib($request, $algo);
        }

        return response()->json(['error' => 'Algoritma tidak dikenal.'], 422);
    }

    private function aes(Request $request)
    {
        $aksi = $request->input('aksi');
        $mode = $request->input('mode');
        $pesan = (string) $request->input('pesan', '');
        $kunci = (string) $request->input('kunci');

        $bits = [16 => 128, 24 => 192, 32 => 256][strlen($kunci)] ?? null;
        if ($bits === null) {
            return response()->json(['error' => 'Kunci AES harus 16, 24, atau 32 byte.'], 422);
        }

        return $this->opensslCrypt($aksi, "aes-{$bits}-{$mode}", $pesan, $kunci);
    }

    private function des(Request $request)
    {
        $aksi = $request->input('aksi');
        $mode = $request->input('mode');
        $pesan = (string) $request->input('pesan', '');
        $kunci = (string) $request->input('kunci');

        if (strlen($kunci) !== 8) {
            return response()->json(['error' => 'Kunci DES harus tepat 8 byte.'], 422);
        }

        return $this->phpseclibCrypt(new DES($mode), $aksi, $mode, $pesan, $kunci, 8);
    }

    private function viaOpenssl(Request $request, string $algo)
    {
        $aksi = $request->input('aksi');
        $mode = $request->input('mode');
        $pesan = (string) $request->input('pesan', '');
        $kunci = (string) $request->input('kunci');

        $nama = $this->openssl[$algo][strlen($kunci)] ?? null;
        if ($nama === null) {
            $valid = implode(', ', array_keys($this->openssl[$algo]));
            return response()->json([
                'error' => 'Panjang kunci ' . strtoupper($algo) . ' tidak sesuai. Pilihan panjang byte: ' . $valid . '.'
            ], 422);
        }

        return $this->opensslCrypt($aksi, "{$nama}-{$mode}", $pesan, $kunci);
    }

    private function opensslCrypt(string $aksi, string $cipher, string $pesan, string $kunci)
    {
        if (!in_array(strtolower($cipher), array_map('strtolower', openssl_get_cipher_methods()), true)) {
            return response()->json(['error' => "Cipher {$cipher} tidak tersedia pada instalasi OpenSSL/PHP ini."], 422);
        }

        $cipher = strtolower($cipher);
        $ivLen = openssl_cipher_iv_length($cipher);
        if ($ivLen === false) {
            return response()->json(['error' => 'Tidak dapat membaca panjang IV cipher.'], 422);
        }

        if ($aksi === 'enkripsi') {
            $iv = $ivLen > 0 ? random_bytes($ivLen) : '';
            $encrypted = openssl_encrypt($pesan, $cipher, $kunci, OPENSSL_RAW_DATA, $iv);
            if ($encrypted === false) {
                return response()->json(['error' => 'Enkripsi gagal. Periksa konfigurasi cipher dan kunci.'], 422);
            }
            return response()->json(['hasil' => base64_encode($iv . $encrypted)]);
        }

        $data = base64_decode($pesan, true);
        if ($data === false || strlen($data) <= $ivLen) {
            return response()->json(['error' => 'Ciphertext tidak valid. Masukkan hasil Base64 dari proses enkripsi.'], 422);
        }

        $iv = $ivLen > 0 ? substr($data, 0, $ivLen) : '';
        $ciphertext = substr($data, $ivLen);
        $decrypted = openssl_decrypt($ciphertext, $cipher, $kunci, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            return response()->json(['error' => 'Dekripsi gagal. Periksa kunci, mode, dan ciphertext.'], 422);
        }
        return response()->json(['hasil' => $decrypted]);
    }

    private function viaSeclib(Request $request, string $algo)
    {
        [$kelas, $ivLen, $panjangValid, $pesanError] = $this->seclib()[$algo];

        $aksi = $request->input('aksi');
        $mode = $request->input('mode');
        $pesan = (string) $request->input('pesan', '');
        $kunci = (string) $request->input('kunci');

        if (!in_array(strlen($kunci), $panjangValid, true)) {
            return response()->json(['error' => $pesanError], 422);
        }

        $cipher = new $kelas($mode);
        if ($algo === 'rijndael') {
            $cipher->setBlockLength(128);
        }

        return $this->phpseclibCrypt($cipher, $aksi, $mode, $pesan, $kunci, $ivLen);
    }

    private function phpseclibCrypt($cipher, string $aksi, string $mode, string $pesan, string $kunci, int $ivLen)
{
    try {
        $cipher->setKey($kunci);

        // ECB tidak membutuhkan IV.
        // Mode lainnya membutuhkan IV.
        $perluIV = ($mode !== 'ecb');

        if ($aksi === 'enkripsi') {
            $iv = '';

            if ($perluIV) {
                $iv = random_bytes($ivLen);
                $cipher->setIV($iv);
            }

            $encrypted = $cipher->encrypt($pesan);

            return response()->json([
                'hasil' => base64_encode($iv . $encrypted)
            ]);
        }

        // Proses dekripsi
        $data = base64_decode($pesan, true);

        $prefixLen = $perluIV ? $ivLen : 0;

        if ($data === false || strlen($data) <= $prefixLen) {
            return response()->json([
                'error' => 'Ciphertext tidak valid. Masukkan hasil Base64 dari proses enkripsi.'
            ], 422);
        }

        if ($perluIV) {
            $iv = substr($data, 0, $ivLen);
            $cipher->setIV($iv);
        }

        $ciphertext = substr($data, $prefixLen);
        $decrypted = $cipher->decrypt($ciphertext);

        if ($decrypted === false) {
            return response()->json([
                'error' => 'Dekripsi gagal. Periksa kunci dan ciphertext.'
            ], 422);
        }

        return response()->json([
            'hasil' => $decrypted
        ]);

    } catch (\Throwable $e) {
        report($e);

        return response()->json([
            'error' => 'Proses cipher gagal. Periksa kunci, mode, dan format ciphertext.'
        ], 422);
    }
}
}
