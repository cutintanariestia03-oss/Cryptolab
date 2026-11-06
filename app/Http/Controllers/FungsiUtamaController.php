<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FungsiUtamaController extends Controller
{
    // 19 algoritma hash yang didukung fungsi hash() bawaan PHP.
    private array $algoritma = [
        'md5', 'sha1',
        'sha224', 'sha256', 'sha384', 'sha512', 'sha512/224', 'sha512/256',
        'sha3-224', 'sha3-256', 'sha3-384', 'sha3-512',
        'ripemd160', 'whirlpool',
        'crc32', 'crc32b', 'adler32', 'fnv1a32', 'fnv1a64',
    ];

    public function proses(Request $request)
    {
        $request->validate([
            'algo'  => 'required|string',
            'pesan' => 'nullable|string',
        ]);

        $algo = $request->input('algo');

        if (!in_array($algo, $this->algoritma, true) || !in_array($algo, hash_algos(), true)) {
            return response()->json(['error' => 'Algoritma hash tidak dikenal atau tidak tersedia di server ini.'], 422);
        }

        $hasil = hash($algo, (string) $request->input('pesan', ''));

        return response()->json(['hasil' => $hasil]);
    }
}