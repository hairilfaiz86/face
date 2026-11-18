<?php
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;

    if (!is_dir(dirname(FAIL_DB))) mkdir(dirname(FAIL_DB), 0775, true);
    $pdo = new PDO('sqlite:' . FAIL_DB, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL;');
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pelajar (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nama TEXT NOT NULL,
            no_murid TEXT UNIQUE,
            kelas TEXT NOT NULL,
            dicipta TEXT DEFAULT (datetime('now','localtime'))
        );
        CREATE TABLE IF NOT EXISTS wajah (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            pelajar_id INTEGER NOT NULL REFERENCES pelajar(id) ON DELETE CASCADE,
            embedding TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS kehadiran (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            pelajar_id INTEGER NOT NULL REFERENCES pelajar(id) ON DELETE CASCADE,
            tarikh TEXT NOT NULL,
            masa TEXT NOT NULL,
            skor REAL,
            status TEXT NOT NULL,
            UNIQUE(pelajar_id, tarikh)
        );
        CREATE INDEX IF NOT EXISTS idx_hadir_tarikh ON kehadiran(tarikh);
        CREATE TABLE IF NOT EXISTS tetapan (
            kunci TEXT PRIMARY KEY,
            nilai TEXT NOT NULL
        );
    ");
    return $pdo;
}

/** Baca semua tetapan (DB mengatasi nilai lalai). */
function semua_tetapan(): array
{
    $simpan = db()->query('SELECT kunci, nilai FROM tetapan')->fetchAll(PDO::FETCH_KEY_PAIR);
    return array_merge(TETAPAN_LALAI, $simpan);
}

function simpan_tetapan(array $data): void
{
    $st = db()->prepare('INSERT INTO tetapan (kunci, nilai) VALUES (?, ?)
        ON CONFLICT(kunci) DO UPDATE SET nilai = excluded.nilai');
    foreach ($data as $k => $v) $st->execute([$k, (string)$v]);
}

function semak_pin(string $pin): bool
{
    $s = db()->prepare("SELECT nilai FROM tetapan WHERE kunci = 'pin_hash'");
    $s->execute();
    $hash = $s->fetchColumn();
    return $hash ? password_verify($pin, $hash) : hash_equals(PIN_LALAI, $pin);
}

// Jadikan tetapan sebagai pemalar untuk kegunaan seluruh sistem
$__t = semua_tetapan();
define('NAMA_SEKOLAH', $__t['nama_sekolah']);
define('SINGKATAN', $__t['singkatan']);
define('MOTTO', $__t['motto']);
define('HAD_LEWAT', substr($__t['had_lewat'], 0, 5) . ':00');
define('AMBANG_PADANAN', (float)$__t['ambang_padanan']);
define('BIL_SAMPEL', (int)$__t['bil_sampel']);
define('TEMPOH_KAD', (int)$__t['tempoh_kad']);
define('BUNYI', $__t['bunyi'] === '1');
unset($__t);

/** Normalkan vektor kepada panjang 1 supaya cosine = hasil darab titik. */
function normalkan(array $v): array
{
    $n = 0.0;
    foreach ($v as $x) $n += $x * $x;
    $n = sqrt($n) ?: 1.0;
    return array_map(fn($x) => round($x / $n, 6), $v);
}

function semak_embedding($v): ?array
{
    if (!is_array($v) || count($v) < 64 || count($v) > 1024) return null;
    foreach ($v as $x) if (!is_numeric($x)) return null;
    return array_map('floatval', $v);
}

function h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Senarai semua pelajar (ikut kelas) dengan status kehadiran pada tarikh tertentu. */
function data_laporan(string $tarikh, string $kelas = ''): array
{
    $sql = 'SELECT p.nama, p.no_murid, p.kelas, k.masa, k.status, k.skor
            FROM pelajar p LEFT JOIN kehadiran k ON k.pelajar_id = p.id AND k.tarikh = ?';
    $arg = [$tarikh];
    if ($kelas !== '') { $sql .= ' WHERE p.kelas = ?'; $arg[] = $kelas; }
    $sql .= ' ORDER BY p.kelas, p.nama';
    $st = db()->prepare($sql);
    $st->execute($arg);
    return $st->fetchAll();
}

function tarikh_melayu(string $tarikh, bool $hari = true): string
{
    $t = strtotime($tarikh);
    $HARI = ['Ahad', 'Isnin', 'Selasa', 'Rabu', 'Khamis', 'Jumaat', 'Sabtu'];
    $BULAN = ['Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun', 'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'];
    $s = date('j', $t) . ' ' . $BULAN[date('n', $t) - 1] . ' ' . date('Y', $t);
    return $hari ? $HARI[date('w', $t)] . ', ' . $s : $s;
}

function senarai_kelas(): array
{
    return db()->query('SELECT DISTINCT kelas FROM pelajar ORDER BY kelas')->fetchAll(PDO::FETCH_COLUMN);
}
