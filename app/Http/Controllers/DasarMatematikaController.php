<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DasarMatematikaController extends Controller
{
    // Batas nilai supaya perkalian tidak melebihi integer 64-bit (1e9 * 1e9 < 9.2e18).
    private const BATAS = 1000000000;

    // Metode => input yang dibutuhkan.
    private array $metode = [
        'modulo'  => ['a', 'n'],
        'gcd'     => ['a', 'b'],
        'lcm'     => ['a', 'b'],
        'eea'     => ['a', 'b'],
        'invers'  => ['a', 'n'],
        'modpow'  => ['a', 'b', 'n'],
        'prima'   => ['a'],
        'faktor'  => ['a'],
        'totient' => ['a'],
        'xor'     => ['a', 'b'],
    ];

    public function proses(Request $request)
    {
        $algo = (string) $request->input('algo');

        if (!isset($this->metode[$algo])) {
            return $this->galat('Metode tidak dikenal.');
        }

        $nilai = [];
        foreach ($this->metode[$algo] as $field) {
            $v = trim((string) $request->input($field, ''));
            if (!preg_match('/^-?\d{1,10}$/', $v)) {
                return $this->galat("Nilai {$field} harus berupa bilangan bulat.");
            }
            $nilai[$field] = (int) $v;
            if (abs($nilai[$field]) > self::BATAS) {
                return $this->galat("Nilai {$field} maksimal ±1.000.000.000.");
            }
        }

        try {
            $hasil = $this->{'hitung' . ucfirst($algo)}($nilai);
            return response()->json(['hasil' => $hasil]);
        } catch (\InvalidArgumentException $e) {
            return $this->galat($e->getMessage());
        }
    }

    private function galat(string $pesan)
    {
        return response()->json(['error' => $pesan], 422);
    }

    private function mod(int $a, int $n): int
    {
        return (($a % $n) + $n) % $n;
    }

    // ---------- 1. Modulo ----------
    private function hitungModulo(array $v): string
    {
        ['a' => $a, 'n' => $n] = $v;
        if ($n <= 0) {
            throw new \InvalidArgumentException('Modulus n harus lebih besar dari 0.');
        }
        return "{$a} mod {$n} = " . $this->mod($a, $n);
    }

    // ---------- 2. FPB / GCD (Euclid) ----------
    private function langkahGcd(int $a, int $b): array
    {
        $a = abs($a);
        $b = abs($b);
        if ($a === 0 && $b === 0) {
            throw new \InvalidArgumentException('a dan b tidak boleh keduanya 0.');
        }
        $baris = [];
        while ($b !== 0) {
            $q = intdiv($a, $b);
            $r = $a % $b;
            $baris[] = "{$a} = {$q} × {$b} + {$r}";
            [$a, $b] = [$b, $r];
        }
        return [$a, $baris];
    }

    private function hitungGcd(array $v): string
    {
        [$g, $baris] = $this->langkahGcd($v['a'], $v['b']);
        return "FPB({$v['a']}, {$v['b']}) = {$g}\n\nLangkah algoritma Euclid:\n" . implode("\n", $baris);
    }

    // ---------- 3. KPK / LCM ----------
    private function hitungLcm(array $v): string
    {
        [$g] = $this->langkahGcd($v['a'], $v['b']);
        $l = intdiv(abs($v['a']), $g) * abs($v['b']);
        return "KPK({$v['a']}, {$v['b']}) = {$l}\n\nRumus: |a × b| / FPB(a, b), dengan FPB = {$g}";
    }

    // ---------- 4. Extended Euclidean Algorithm ----------
    private function eea(int $a, int $b): array
    {
        [$r0, $r1] = [$a, $b];
        [$x0, $x1] = [1, 0];
        [$y0, $y1] = [0, 1];
        while ($r1 !== 0) {
            $q = intdiv($r0, $r1);
            [$r0, $r1] = [$r1, $r0 - $q * $r1];
            [$x0, $x1] = [$x1, $x0 - $q * $x1];
            [$y0, $y1] = [$y1, $y0 - $q * $y1];
        }
        if ($r0 < 0) {
            [$r0, $x0, $y0] = [-$r0, -$x0, -$y0];
        }
        return [$r0, $x0, $y0];
    }

    private function hitungEea(array $v): string
    {
        ['a' => $a, 'b' => $b] = $v;
        if ($a === 0 && $b === 0) {
            throw new \InvalidArgumentException('a dan b tidak boleh keduanya 0.');
        }
        [$g, $x, $y] = $this->eea($a, $b);
        return "FPB({$a}, {$b}) = {$g}\nx = {$x}\ny = {$y}\n\n"
             . "Verifikasi: ({$a} × {$x}) + ({$b} × {$y}) = " . ($a * $x + $b * $y);
    }

    // ---------- 5. Invers Modular ----------
    private function hitungInvers(array $v): string
    {
        ['a' => $a, 'n' => $n] = $v;
        if ($n <= 1) {
            throw new \InvalidArgumentException('Modulus n harus lebih besar dari 1.');
        }
        $a2 = $this->mod($a, $n);
        [$g, $x] = $this->eea($a2, $n);
        if ($g !== 1) {
            throw new \InvalidArgumentException("{$a} tidak memiliki invers modulo {$n} karena FPB({$a}, {$n}) = {$g} (harus 1).");
        }
        $inv = $this->mod($x, $n);
        return "{$a}⁻¹ mod {$n} = {$inv}\n\nVerifikasi: ({$a} × {$inv}) mod {$n} = " . (($a2 * $inv) % $n);
    }

    // ---------- 6. Eksponen Modular ----------
    private function hitungModpow(array $v): string
    {
        ['a' => $a, 'b' => $b, 'n' => $n] = $v;
        if ($n <= 0) {
            throw new \InvalidArgumentException('Modulus n harus lebih besar dari 0.');
        }
        if ($b < 0) {
            throw new \InvalidArgumentException('Eksponen b tidak boleh negatif.');
        }
        $hasil = 1 % $n;
        $basis = $this->mod($a, $n);
        $e = $b;
        while ($e > 0) {
            if ($e & 1) {
                $hasil = ($hasil * $basis) % $n;
            }
            $basis = ($basis * $basis) % $n;
            $e >>= 1;
        }
        return "{$a}^{$b} mod {$n} = {$hasil}\n\nMetode: square-and-multiply";
    }

    // ---------- 7. Uji Bilangan Prima ----------
    private function hitungPrima(array $v): string
    {
        $a = $v['a'];
        if ($a < 2) {
            return "{$a} bukan bilangan prima (bilangan prima dimulai dari 2).";
        }
        for ($i = 2; $i * $i <= $a; $i++) {
            if ($a % $i === 0) {
                return "{$a} BUKAN bilangan prima.\nPembagi terkecil: {$i} (karena {$a} = {$i} × " . intdiv($a, $i) . ')';
            }
        }
        return "{$a} adalah bilangan PRIMA.";
    }

    // ---------- 8. Faktorisasi Prima ----------
    private function faktorisasi(int $a): array
    {
        $faktor = [];
        for ($i = 2; $i * $i <= $a; $i++) {
            while ($a % $i === 0) {
                $faktor[$i] = ($faktor[$i] ?? 0) + 1;
                $a = intdiv($a, $i);
            }
        }
        if ($a > 1) {
            $faktor[$a] = ($faktor[$a] ?? 0) + 1;
        }
        return $faktor;
    }

    private function formatFaktor(array $faktor): string
    {
        $bagian = [];
        foreach ($faktor as $p => $e) {
            $bagian[] = $e > 1 ? "{$p}^{$e}" : (string) $p;
        }
        return implode(' × ', $bagian);
    }

    private function hitungFaktor(array $v): string
    {
        $a = $v['a'];
        if ($a < 2) {
            throw new \InvalidArgumentException('Bilangan a harus lebih besar atau sama dengan 2.');
        }
        return "{$a} = " . $this->formatFaktor($this->faktorisasi($a));
    }

    // ---------- 9. Fungsi Totient Euler ----------
    private function hitungTotient(array $v): string
    {
        $a = $v['a'];
        if ($a < 1) {
            throw new \InvalidArgumentException('Bilangan a harus lebih besar atau sama dengan 1.');
        }
        $faktor = $a > 1 ? $this->faktorisasi($a) : [];
        $phi = $a;
        foreach (array_keys($faktor) as $p) {
            $phi = intdiv($phi, $p) * ($p - 1);
        }
        $uraian = $faktor ? $this->formatFaktor($faktor) : '1';
        return "φ({$a}) = {$phi}\n\nFaktorisasi: {$a} = {$uraian}\nRumus: φ(n) = n × Π (1 − 1/p)";
    }

    // ---------- 10. XOR ----------
    private function hitungXor(array $v): string
    {
        ['a' => $a, 'b' => $b] = $v;
        if ($a < 0 || $b < 0) {
            throw new \InvalidArgumentException('Untuk XOR, a dan b harus bilangan non-negatif.');
        }
        $r = $a ^ $b;
        $lebar = max(strlen(decbin($a)), strlen(decbin($b)), strlen(decbin($r)));
        $f = fn(int $x) => str_pad(decbin($x), $lebar, '0', STR_PAD_LEFT);
        return "{$a} XOR {$b} = {$r}\n\n"
             . "  " . $f($a) . "  ({$a})\n"
             . "^ " . $f($b) . "  ({$b})\n"
             . "= " . $f($r) . "  ({$r})";
    }
}