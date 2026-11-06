<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StreamCipherController extends Controller
{
    public function proses(Request $request)
    {
        $algo  = $request->input('algo');
        $aksi  = $request->input('aksi');
        $pesan = (string) $request->input('pesan');
        $kunci = (string) $request->input('kunci');

        if ($kunci === '') {
            return response()->json(['error' => 'Kunci tidak boleh kosong.']);
        }

        if ($algo === 'rc4') {
            return $this->rc4($aksi, $pesan, $kunci);
        }
        if ($algo === 'chacha20') {
            return $this->chacha($aksi, $pesan, $kunci);
        }
        if ($algo === 'salsa20') {
            return $this->salsa20($aksi, $pesan, $kunci);
        }
        if ($algo === 'a51') {
            return $this->a51($aksi, $pesan, $kunci);
        }
        if ($algo === 'trivium') {
            return $this->trivium($aksi, $pesan, $kunci);
        }
        if ($algo === 'grain') {
            return $this->grain($aksi, $pesan, $kunci);
        }
        if ($algo === 'spritz') {
            return $this->spritz($aksi, $pesan, $kunci);
        }
        return response()->json(['error' => 'Algoritma tidak dikenal.']);
    }

    // RC4 ditulis manual (KSA + PRGA) supaya prosesnya terlihat
    private function rc4Crypt(string $data, string $key): string
    {
        $S = range(0, 255);
        $j = 0;
        $keyLen = strlen($key);
        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $S[$i] + ord($key[$i % $keyLen])) % 256;
            [$S[$i], $S[$j]] = [$S[$j], $S[$i]];
        }
        $i = $j = 0;
        $out = '';
        $len = strlen($data);
        for ($n = 0; $n < $len; $n++) {
            $i = ($i + 1) % 256;
            $j = ($j + $S[$i]) % 256;
            [$S[$i], $S[$j]] = [$S[$j], $S[$i]];
            $k = $S[($S[$i] + $S[$j]) % 256];
            $out .= chr(ord($data[$n]) ^ $k);
        }
        return $out;
    }

    private function rc4(string $aksi, string $pesan, string $kunci)
    {
        if ($aksi === 'enkripsi') {
            return response()->json(['hasil' => base64_encode($this->rc4Crypt($pesan, $kunci))]);
        }
        $data = base64_decode($pesan, true);
        if ($data === false) {
            return response()->json(['error' => 'Ciphertext tidak valid (harus Base64 hasil enkripsi).']);
        }
        return response()->json(['hasil' => $this->rc4Crypt($data, $kunci)]);
    }

    private function chacha(string $aksi, string $pesan, string $kunci)
    {
        // Kunci ChaCha20 harus 32 byte: diturunkan dari kunci input dengan SHA-256
        $key = hash('sha256', $kunci, true);
        $nLen = \SODIUM_CRYPTO_STREAM_XCHACHA20_NONCEBYTES;

        if ($aksi === 'enkripsi') {
            $nonce = random_bytes($nLen);
            $cipher = \sodium_crypto_stream_xchacha20_xor($pesan, $nonce, $key);
            return response()->json(['hasil' => base64_encode($nonce . $cipher)]);
        }

        $data = base64_decode($pesan, true);
        if ($data === false || strlen($data) < $nLen) {
            return response()->json(['error' => 'Ciphertext tidak valid (harus Base64 hasil enkripsi).']);
        }
        $nonce = substr($data, 0, $nLen);
        $plain = \sodium_crypto_stream_xchacha20_xor(substr($data, $nLen), $nonce, $key);
        return response()->json(['hasil' => $plain]);
    }

    // ================= SALSA20 (ditulis manual, 20 ronde) =================
    private function salsaRotl(int $x, int $n): int
    {
        return (($x << $n) & 0xFFFFFFFF) | ($x >> (32 - $n));
    }

    private function salsaQuarter(array &$x, int $a, int $b, int $c, int $d): void
    {
        $x[$b] ^= $this->salsaRotl(($x[$a] + $x[$d]) & 0xFFFFFFFF, 7);
        $x[$c] ^= $this->salsaRotl(($x[$b] + $x[$a]) & 0xFFFFFFFF, 9);
        $x[$d] ^= $this->salsaRotl(($x[$c] + $x[$b]) & 0xFFFFFFFF, 13);
        $x[$a] ^= $this->salsaRotl(($x[$d] + $x[$c]) & 0xFFFFFFFF, 18);
    }

    // Menghasilkan 64 byte keystream. Key 32 byte, nonce 8 byte.
    private function salsaBlock(string $key, string $nonce, int $counter): string
    {
        $c = array_values(unpack('V4', 'expand 32-byte k'));
        $k = array_values(unpack('V8', $key));
        $n = array_values(unpack('V2', $nonce));

        $state = [
            $c[0], $k[0], $k[1], $k[2],
            $k[3], $c[1], $n[0], $n[1],
            $counter & 0xFFFFFFFF, ($counter >> 32) & 0xFFFFFFFF, $c[2], $k[4],
            $k[5], $k[6], $k[7], $c[3],
        ];

        $x = $state;
        for ($i = 0; $i < 10; $i++) { // 10 double round = 20 ronde
            // column round
            $this->salsaQuarter($x, 0, 4, 8, 12);
            $this->salsaQuarter($x, 5, 9, 13, 1);
            $this->salsaQuarter($x, 10, 14, 2, 6);
            $this->salsaQuarter($x, 15, 3, 7, 11);
            // row round
            $this->salsaQuarter($x, 0, 1, 2, 3);
            $this->salsaQuarter($x, 5, 6, 7, 4);
            $this->salsaQuarter($x, 10, 11, 8, 9);
            $this->salsaQuarter($x, 15, 12, 13, 14);
        }

        $out = '';
        for ($i = 0; $i < 16; $i++) {
            $out .= pack('V', ($x[$i] + $state[$i]) & 0xFFFFFFFF);
        }
        return $out;
    }

    private function salsaCrypt(string $data, string $key, string $nonce): string
    {
        $out = '';
        $len = strlen($data);
        for ($i = 0, $blok = 0; $i < $len; $i += 64, $blok++) {
            $chunk = substr($data, $i, 64);
            $ks = substr($this->salsaBlock($key, $nonce, $blok), 0, strlen($chunk));
            $out .= $chunk ^ $ks;
        }
        return $out;
    }

    private function salsa20(string $aksi, string $pesan, string $kunci)
    {
        // Kunci Salsa20 harus 32 byte: diturunkan dari kunci input dengan SHA-256
        $key = hash('sha256', $kunci, true);
        $nLen = 8;

        if ($aksi === 'enkripsi') {
            $nonce = random_bytes($nLen);
            $cipher = $this->salsaCrypt($pesan, $key, $nonce);
            return response()->json(['hasil' => base64_encode($nonce . $cipher)]);
        }

        $data = base64_decode($pesan, true);
        if ($data === false || strlen($data) < $nLen) {
            return response()->json(['error' => 'Ciphertext tidak valid (harus Base64 hasil enkripsi).']);
        }
        $nonce = substr($data, 0, $nLen);
        $plain = $this->salsaCrypt(substr($data, $nLen), $key, $nonce);
        return response()->json(['hasil' => $plain]);
    }

    // ================= A5/1 (ditulis manual, 3 LFSR) =================
    // Geser register: bit baru masuk di indeks 0, hasil XOR dari titik tap.
    private function a51Shift(array &$reg, array $taps, int $extra = 0): void
    {
        $fb = $extra;
        foreach ($taps as $t) {
            $fb ^= $reg[$t];
        }
        array_pop($reg);
        array_unshift($reg, $fb);
    }

    private function a51Clock(array &$r1, array &$r2, array &$r3): void
    {
        // Aturan mayoritas: hanya register yang bit clock-nya sama dengan mayoritas yang bergeser
        $m = ($r1[8] + $r2[10] + $r3[10]) >= 2 ? 1 : 0;
        if ($r1[8] === $m) {
            $this->a51Shift($r1, [13, 16, 17, 18]);
        }
        if ($r2[10] === $m) {
            $this->a51Shift($r2, [20, 21]);
        }
        if ($r3[10] === $m) {
            $this->a51Shift($r3, [7, 20, 21, 22]);
        }
    }

    // Key 8 byte (64 bit), frame 22 bit. Mengembalikan keystream sepanjang $panjang byte.
    private function a51Keystream(string $key, int $frame, int $panjang): string
    {
        $r1 = array_fill(0, 19, 0);
        $r2 = array_fill(0, 22, 0);
        $r3 = array_fill(0, 23, 0);

        // 1) masukkan 64 bit key (semua register digeser tiap bit)
        for ($i = 0; $i < 8; $i++) {
            for ($j = 0; $j < 8; $j++) {
                $bit = (ord($key[$i]) >> $j) & 1;
                $this->a51Shift($r1, [13, 16, 17, 18], $bit);
                $this->a51Shift($r2, [20, 21], $bit);
                $this->a51Shift($r3, [7, 20, 21, 22], $bit);
            }
        }
        // 2) masukkan 22 bit frame number
        for ($j = 0; $j < 22; $j++) {
            $bit = ($frame >> $j) & 1;
            $this->a51Shift($r1, [13, 16, 17, 18], $bit);
            $this->a51Shift($r2, [20, 21], $bit);
            $this->a51Shift($r3, [7, 20, 21, 22], $bit);
        }
        // 3) 100 clock dengan aturan mayoritas, output dibuang
        for ($i = 0; $i < 100; $i++) {
            $this->a51Clock($r1, $r2, $r3);
        }
        // 4) hasilkan keystream
        $ks = '';
        for ($b = 0; $b < $panjang; $b++) {
            $v = 0;
            for ($i = 0; $i < 8; $i++) {
                $this->a51Clock($r1, $r2, $r3);
                $v = ($v << 1) | ($r1[18] ^ $r2[21] ^ $r3[22]);
            }
            $ks .= chr($v);
        }
        return $ks;
    }

    private function a51(string $aksi, string $pesan, string $kunci)
    {
        // Kunci A5/1 harus 64 bit: diambil 8 byte pertama dari SHA-256 kunci input
        $key = substr(hash('sha256', $kunci, true), 0, 8);

        if ($aksi === 'enkripsi') {
            $frame = random_int(0, 0x3FFFFF); // 22 bit
            $cipher = $pesan ^ $this->a51Keystream($key, $frame, strlen($pesan));
            return response()->json(['hasil' => base64_encode(pack('N', $frame) . $cipher)]);
        }

        $data = base64_decode($pesan, true);
        if ($data === false || strlen($data) < 4) {
            return response()->json(['error' => 'Ciphertext tidak valid (harus Base64 hasil enkripsi).']);
        }
        $frame = unpack('N', substr($data, 0, 4))[1];
        $cipher = substr($data, 4);
        $plain = $cipher ^ $this->a51Keystream($key, $frame, strlen($cipher));
        return response()->json(['hasil' => $plain]);
    }

    // ================= TRIVIUM (ditulis manual, state 288 bit) =================
    // Key 80 bit, IV 80 bit. Urutan bit mengikuti vektor uji resmi eSTREAM.
    private function trivBits(string $s): array
    {
        $bits = [];
        for ($i = 0; $i < 80; $i++) {
            $bits[] = (ord($s[intdiv($i, 8)]) >> ($i % 8)) & 1;
        }
        return array_reverse($bits);
    }

    private function trivKeystream(string $key, string $iv, int $panjang): string
    {
        // Tiga register: a (93 bit), b (84 bit), c (111 bit)
        $a = array_merge($this->trivBits($key), array_fill(0, 13, 0));
        $b = array_merge($this->trivBits($iv), array_fill(0, 4, 0));
        $c = array_merge(array_fill(0, 108, 0), [1, 1, 1]);

        $out = '';
        $v = 0;
        $total = 1152 + $panjang * 8; // 4 x 288 ronde pemanasan, lalu keystream
        for ($r = 0; $r < $total; $r++) {
            $t1 = $a[65] ^ $a[92];
            $t2 = $b[68] ^ $b[83];
            $t3 = $c[65] ^ $c[110];
            $z = $t1 ^ $t2 ^ $t3;
            $t1 ^= ($a[90] & $a[91]) ^ $b[77];
            $t2 ^= ($b[81] & $b[82]) ^ $c[86];
            $t3 ^= ($c[108] & $c[109]) ^ $a[68];

            array_pop($a); array_unshift($a, $t3);
            array_pop($b); array_unshift($b, $t1);
            array_pop($c); array_unshift($c, $t2);

            if ($r >= 1152) {
                $k = $r - 1152;
                if ($k % 8 === 0) {
                    $v = 0;
                }
                $v |= $z << ($k % 8);
                if ($k % 8 === 7) {
                    $out .= chr($v);
                }
            }
        }
        return $out;
    }

    private function trivium(string $aksi, string $pesan, string $kunci)
    {
        // Kunci Trivium 80 bit: 10 byte pertama dari SHA-256 kunci input. IV 10 byte acak.
        $key = substr(hash('sha256', $kunci, true), 0, 10);
        $ivLen = 10;

        if ($aksi === 'enkripsi') {
            $iv = random_bytes($ivLen);
            $cipher = $pesan ^ $this->trivKeystream($key, $iv, strlen($pesan));
            return response()->json(['hasil' => base64_encode($iv . $cipher)]);
        }

        $data = base64_decode($pesan, true);
        if ($data === false || strlen($data) < $ivLen) {
            return response()->json(['error' => 'Ciphertext tidak valid (harus Base64 hasil enkripsi).']);
        }
        $iv = substr($data, 0, $ivLen);
        $cipher = substr($data, $ivLen);
        $plain = $cipher ^ $this->trivKeystream($key, $iv, strlen($cipher));
        return response()->json(['hasil' => $plain]);
    }

    // ================= GRAIN v1 (ditulis manual, LFSR + NFSR 80 bit) =================
    // Key 80 bit, IV 64 bit.
    private function grainBits(string $s, int $n): array
    {
        $bits = [];
        for ($i = 0; $i < $n; $i++) {
            $bits[] = (ord($s[intdiv($i, 8)]) >> ($i % 8)) & 1;
        }
        return $bits;
    }

    // Satu clock. Mengembalikan bit keystream z. Saat $init = true, z diumpankan balik.
    private function grainClock(array &$s, array &$b, bool $init): int
    {
        // fungsi h(x) dari LFSR dan NFSR
        $x0 = $s[3]; $x1 = $s[25]; $x2 = $s[46]; $x3 = $s[64]; $x4 = $b[63];
        $h = $x1 ^ $x4 ^ ($x0 & $x3) ^ ($x2 & $x3) ^ ($x3 & $x4)
           ^ ($x0 & $x1 & $x2) ^ ($x0 & $x2 & $x3) ^ ($x0 & $x2 & $x4)
           ^ ($x1 & $x2 & $x4) ^ ($x2 & $x3 & $x4);

        $z = $b[1] ^ $b[2] ^ $b[4] ^ $b[10] ^ $b[31] ^ $b[43] ^ $b[56] ^ $h;

        // umpan balik LFSR
        $fs = $s[62] ^ $s[51] ^ $s[38] ^ $s[23] ^ $s[13] ^ $s[0];

        // umpan balik NFSR (linear + suku non-linear)
        $fb = $s[0] ^ $b[63] ^ $b[60] ^ $b[52] ^ $b[45] ^ $b[37] ^ $b[33] ^ $b[28]
            ^ $b[21] ^ $b[15] ^ $b[9] ^ $b[0]
            ^ ($b[63] & $b[60]) ^ ($b[37] & $b[33]) ^ ($b[15] & $b[9])
            ^ ($b[60] & $b[52] & $b[45]) ^ ($b[33] & $b[28] & $b[21])
            ^ ($b[63] & $b[45] & $b[28] & $b[9])
            ^ ($b[60] & $b[52] & $b[37] & $b[33])
            ^ ($b[63] & $b[60] & $b[21] & $b[15])
            ^ ($b[63] & $b[60] & $b[52] & $b[45] & $b[37])
            ^ ($b[33] & $b[28] & $b[21] & $b[15] & $b[9])
            ^ ($b[52] & $b[45] & $b[37] & $b[33] & $b[28] & $b[21]);

        if ($init) {
            $fs ^= $z;
            $fb ^= $z;
        }

        array_shift($s); $s[] = $fs;
        array_shift($b); $b[] = $fb;
        return $z;
    }

    private function grainKeystream(string $key, string $iv, int $panjang): string
    {
        $b = $this->grainBits($key, 80);
        $s = array_merge($this->grainBits($iv, 64), array_fill(0, 16, 1));

        for ($i = 0; $i < 160; $i++) {   // 160 clock inisialisasi
            $this->grainClock($s, $b, true);
        }

        $out = '';
        for ($n = 0; $n < $panjang; $n++) {
            $v = 0;
            for ($i = 0; $i < 8; $i++) {
                $v |= $this->grainClock($s, $b, false) << $i;
            }
            $out .= chr($v);
        }
        return $out;
    }

    private function grain(string $aksi, string $pesan, string $kunci)
    {
        // Kunci Grain 80 bit: 10 byte pertama dari SHA-256 kunci input. IV 8 byte acak.
        $key = substr(hash('sha256', $kunci, true), 0, 10);
        $ivLen = 8;

        if ($aksi === 'enkripsi') {
            $iv = random_bytes($ivLen);
            $cipher = $pesan ^ $this->grainKeystream($key, $iv, strlen($pesan));
            return response()->json(['hasil' => base64_encode($iv . $cipher)]);
        }

        $data = base64_decode($pesan, true);
        if ($data === false || strlen($data) < $ivLen) {
            return response()->json(['error' => 'Ciphertext tidak valid (harus Base64 hasil enkripsi).']);
        }
        $iv = substr($data, 0, $ivLen);
        $cipher = substr($data, $ivLen);
        $plain = $cipher ^ $this->grainKeystream($key, $iv, strlen($cipher));
        return response()->json(['hasil' => $plain]);
    }

    // ================= SPRITZ (ditulis manual, pengganti RC4) =================
    private function spritzNew(): array
    {
        return ['S' => range(0, 255), 'i' => 0, 'j' => 0, 'k' => 0, 'z' => 0, 'a' => 0, 'w' => 1];
    }

    private function spritzUpdate(array &$st): void
    {
        $st['i'] = ($st['i'] + $st['w']) & 255;
        $st['j'] = ($st['k'] + $st['S'][($st['j'] + $st['S'][$st['i']]) & 255]) & 255;
        $st['k'] = ($st['i'] + $st['k'] + $st['S'][$st['j']]) & 255;
        [$st['S'][$st['i']], $st['S'][$st['j']]] = [$st['S'][$st['j']], $st['S'][$st['i']]];
    }

    private function spritzWhip(array &$st, int $r): void
    {
        for ($v = 0; $v < $r; $v++) {
            $this->spritzUpdate($st);
        }
        $st['w'] = ($st['w'] + 2) & 255; // w tetap ganjil (relatif prima dengan 256)
    }

    private function spritzCrush(array &$st): void
    {
        for ($v = 0; $v < 128; $v++) {
            $y = 255 - $v;
            if ($st['S'][$v] > $st['S'][$y]) {
                [$st['S'][$v], $st['S'][$y]] = [$st['S'][$y], $st['S'][$v]];
            }
        }
    }

    private function spritzShuffle(array &$st): void
    {
        $this->spritzWhip($st, 512);
        $this->spritzCrush($st);
        $this->spritzWhip($st, 512);
        $this->spritzCrush($st);
        $this->spritzWhip($st, 512);
        $st['a'] = 0;
    }

    private function spritzNibble(array &$st, int $x): void
    {
        if ($st['a'] === 128) {
            $this->spritzShuffle($st);
        }
        [$st['S'][$st['a']], $st['S'][128 + $x]] = [$st['S'][128 + $x], $st['S'][$st['a']]];
        $st['a']++;
    }

    private function spritzAbsorb(array &$st, string $data): void
    {
        $len = strlen($data);
        for ($n = 0; $n < $len; $n++) {
            $b = ord($data[$n]);
            $this->spritzNibble($st, $b & 15);
            $this->spritzNibble($st, $b >> 4);
        }
    }

    private function spritzAbsorbStop(array &$st): void
    {
        if ($st['a'] === 128) {
            $this->spritzShuffle($st);
        }
        $st['a']++;
    }

    private function spritzDrip(array &$st): int
    {
        if ($st['a'] > 0) {
            $this->spritzShuffle($st);
        }
        $this->spritzUpdate($st);
        $st['z'] = $st['S'][($st['j'] + $st['S'][($st['i'] + $st['S'][($st['z'] + $st['k']) & 255]) & 255]) & 255];
        return $st['z'];
    }

    // IV boleh kosong (dipakai untuk uji vektor resmi).
    private function spritzKeystream(string $key, string $iv, int $panjang): string
    {
        $st = $this->spritzNew();
        $this->spritzAbsorb($st, $key);
        if ($iv !== '') {
            $this->spritzAbsorbStop($st);
            $this->spritzAbsorb($st, $iv);
        }
        $out = '';
        for ($n = 0; $n < $panjang; $n++) {
            $out .= chr($this->spritzDrip($st));
        }
        return $out;
    }

    private function spritz(string $aksi, string $pesan, string $kunci)
    {
        $ivLen = 8;

        if ($aksi === 'enkripsi') {
            $iv = random_bytes($ivLen);
            $cipher = $pesan ^ $this->spritzKeystream($kunci, $iv, strlen($pesan));
            return response()->json(['hasil' => base64_encode($iv . $cipher)]);
        }

        $data = base64_decode($pesan, true);
        if ($data === false || strlen($data) < $ivLen) {
            return response()->json(['error' => 'Ciphertext tidak valid (harus Base64 hasil enkripsi).']);
        }
        $iv = substr($data, 0, $ivLen);
        $cipher = substr($data, $ivLen);
        $plain = $cipher ^ $this->spritzKeystream($kunci, $iv, strlen($cipher));
        return response()->json(['hasil' => $plain]);
    }
}