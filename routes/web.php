<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlockCipherController;
use App\Http\Controllers\StreamCipherController;
use App\Http\Controllers\FungsiUtamaController;
use App\Http\Controllers\DasarMatematikaController;

Route::view('/', 'dashboard');
Route::view('/block-cipher', 'block-cipher');
Route::post('/block-cipher/proses', [BlockCipherController::class, 'proses']);
Route::view('/stream-cipher', 'stream-cipher');
Route::post('/stream-cipher/proses', [StreamCipherController::class, 'proses']);
Route::view('/fungsi-utama', 'fungsi-utama');
Route::post('/fungsi-utama/proses', [FungsiUtamaController::class, 'proses']);
Route::view('/dasar-matematika', 'dasar-matematika');
Route::post('/dasar-matematika/proses', [DasarMatematikaController::class, 'proses']);