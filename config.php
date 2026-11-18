<?php
// ==== Nilai lalai sistem ====
// Kebanyakan tetapan boleh diubah di halaman Tetapan (dashboard admin).
// Nilai di sini hanya digunakan sebelum tetapan pertama disimpan.

const TETAPAN_LALAI = [
    'nama_sekolah'   => 'SK Contoh Harapan',
    'singkatan'      => 'SKCH',
    'motto'          => 'Berilmu, Berakhlak, Berjaya',
    'had_lewat'      => '07:30',   // selepas masa ini ditanda "Lewat"
    'ambang_padanan' => '0.90',    // cosine similarity 0–1, lebih tinggi = lebih ketat
    'bil_sampel'     => '5',       // sampel wajah semasa pendaftaran
    'tempoh_kad'     => '4',       // saat kad ucapan dipaparkan di kiosk
    'bunyi'          => '1',       // bunyi di kiosk (1 = hidup)
];

// PIN admin lalai. Selepas PIN ditukar di halaman Tetapan, nilai ini tidak digunakan lagi.
const PIN_LALAI = '1234';

// Lokasi fail SQLite. Di Docker/Dokploy ditetapkan melalui env DB_PATH (lihat Dockerfile).
define('FAIL_DB', getenv('DB_PATH') ?: __DIR__ . '/data/kehadiran.sqlite');

date_default_timezone_set('Asia/Kuala_Lumpur');
