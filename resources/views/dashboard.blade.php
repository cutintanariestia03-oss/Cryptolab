@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')

<style>
    .hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
        margin-bottom: 35px;
    }

    .hero h1 {
        font-size: 3rem;
        font-weight: 700;
        margin: 0;
    }

    .hero h1 span { color: #2f7bff; }

    .hero p { color: #b8c7e0; }

    .shield-wrap {
        width: 280px;
        flex-shrink: 0;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 24px;
        width: 100%;
    }

    .menu-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 125px;
        padding: 24px 28px;
        background: #0d2142;
        border: 1px solid #16315c;
        border-radius: 13px;
        text-decoration: none;
        color: #e6edf7;
        transition: .2s;
    }

    .menu-card:hover {
        border-color: #2f7bff;
        background: #10294f;
        color: #fff;
        transform: translateY(-3px);
    }

    .menu-card .icon {
        font-size: 2.5rem;
        color: #2f7bff;
        margin-right: 20px;
    }

    .menu-card .title {
        font-size: 1.1rem;
        font-weight: 600;
    }

    .menu-card .sub {
        color: #9fb2d1;
        font-size: .9rem;
        margin-top: 5px;
    }

    .menu-card .arrow {
        width: 36px;
        height: 36px;
        flex-shrink: 0;
        border-radius: 50%;
        background: #16315c;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @media (max-width: 768px) {
        .hero h1 { font-size: 2.3rem; }
        .shield-wrap { display: none; }

        .dashboard-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .menu-card {
            min-height: 105px;
            padding: 18px;
        }
    }
</style>

<div class="hero">
    <div>
        <h5 class="fw-normal">Selamat datang di</h5>
        <h1>Crypto<span>Lab</span></h1>

    <p class="mt-3 mb-1 fs-5">
        Aplikasi Implementasi Algoritma Kriptografi
    </p>

    <p>
        Pilih menu di bawah untuk mulai mencoba<br>
        berbagai metode kriptografi.
    </p>
</div>

<div class="shield-wrap">
    <svg viewBox="0 0 240 260" width="260" height="280"
         xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="sg" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#3b8cff"/>
                <stop offset="1" stop-color="#0b3a9c"/>
            </linearGradient>
            <filter id="glow" x="-50%" y="-50%" width="200%" height="200%">
                <feGaussianBlur stdDeviation="8" result="b"/>
                <feMerge>
                    <feMergeNode in="b"/>
                    <feMergeNode in="SourceGraphic"/>
                </feMerge>
            </filter>
        </defs>

        <ellipse cx="120" cy="140" rx="115" ry="34"
            transform="rotate(-20 120 140)"
            fill="none" stroke="#2f7bff" stroke-width="2" opacity=".7"/>

        <path d="M120 20 L195 50 V120 C195 170 160 208 120 232
            C80 208 45 170 45 120 V50 Z"
            fill="url(#sg)" stroke="#6db0ff" stroke-width="3"
            filter="url(#glow)"/>

        <path d="M120 38 L180 61 V120 C180 160 152 192 120 212
            C88 192 60 160 60 120 V61 Z"
            fill="none" stroke="#9cc8ff" stroke-width="1.5" opacity=".6"/>

        <path d="M98 108 V95 a22 22 0 0 1 44 0 V108"
            fill="none" stroke="#e8f1ff" stroke-width="9"
            stroke-linecap="round"/>

        <rect x="88" y="106" width="64" height="52" rx="9"
            fill="#e8f1ff"/>

        <circle cx="120" cy="127" r="7" fill="#1a4fb8"/>
        <rect x="117" y="130" width="6" height="14" rx="3"
            fill="#1a4fb8"/>
    </svg>
</div>

</div>

<div class="dashboard-grid">

<a href="/block-cipher" class="menu-card">
    <div class="d-flex align-items-center">
        <i class="bi bi-lock-fill icon"></i>
        <div>
            <div class="title">Block Cipher</div>
            <div class="sub">10 Algoritma • 7 Mode Operasi</div>
        </div>
    </div>
    <div class="arrow">
        <i class="bi bi-chevron-right"></i>
    </div>
</a>

<a href="/stream-cipher" class="menu-card">
    <div class="d-flex align-items-center">
        <i class="bi bi-shuffle icon"></i>
        <div>
            <div class="title">Stream Cipher</div>
            <div class="sub">7 Algoritma Stream Cipher</div>
        </div>
    </div>
    <div class="arrow">
        <i class="bi bi-chevron-right"></i>
    </div>
</a>

<a href="/fungsi-utama" class="menu-card">
    <div class="d-flex align-items-center">
        <i class="bi bi-fingerprint icon"></i>
        <div>
            <div class="title">Fungsi Utama</div>
            <div class="sub">19 Pilihan Metode Kriptografi</div>
        </div>
    </div>
    <div class="arrow">
        <i class="bi bi-chevron-right"></i>
    </div>
</a>

<a href="/dasar-matematika" class="menu-card">
    <div class="d-flex align-items-center">
        <i class="bi bi-calculator icon"></i>
        <div>
            <div class="title">Dasar Matematika</div>
            <div class="sub">10 Metode Dasar Matematika Kriptografi</div>
        </div>
    </div>
    <div class="arrow">
        <i class="bi bi-chevron-right"></i>
    </div>
</a>
</div>
@endsection
