<?php
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');

function balas(array $data, int $kod = 200): never
{
    http_response_code($kod);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$tindakan = $_GET['t'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    switch ($tindakan) {

        // ---------- Kiosk: padankan embedding & rekod kehadiran ----------
        case 'imbas':
            $emb = semak_embedding($input['embedding'] ?? null);
            if (!$emb) balas(['ok' => false, 'mesej' => 'Embedding tidak sah.'], 400);
            $q = normalkan($emb);
            $dim = count($q);

            $terbaik = null;
            $skorTerbaik = -1.0;
            $stmt = db()->query('SELECT pelajar_id, embedding FROM wajah');
            while ($r = $stmt->fetch()) {
                $v = json_decode($r['embedding'], true);
                if (count($v) !== $dim) continue;
                $s = 0.0;
                for ($i = 0; $i < $dim; $i++) $s += $q[$i] * $v[$i];
                if ($s > $skorTerbaik) { $skorTerbaik = $s; $terbaik = (int)$r['pelajar_id']; }
            }

            if ($terbaik === null || $skorTerbaik < AMBANG_PADANAN) {
                balas(['ok' => true, 'dikenali' => false, 'skor' => round($skorTerbaik, 3)]);
            }

            $p = db()->prepare('SELECT id, nama, kelas FROM pelajar WHERE id = ?');
            $p->execute([$terbaik]);
            $pelajar = $p->fetch();

            $tarikh = date('Y-m-d');
            $masa = date('H:i:s');
            $status = $masa > HAD_LEWAT ? 'lewat' : 'hadir';

            $ins = db()->prepare('INSERT OR IGNORE INTO kehadiran (pelajar_id, tarikh, masa, skor, status) VALUES (?,?,?,?,?)');
            $ins->execute([$terbaik, $tarikh, $masa, round($skorTerbaik, 4), $status]);
            $baru = $ins->rowCount() > 0;

            if (!$baru) {
                $s = db()->prepare('SELECT masa, status FROM kehadiran WHERE pelajar_id = ? AND tarikh = ?');
                $s->execute([$terbaik, $tarikh]);
                $sedia = $s->fetch();
                $masa = $sedia['masa'];
                $status = $sedia['status'];
            }

            balas([
                'ok' => true,
                'dikenali' => true,
                'baru' => $baru,
                'nama' => $pelajar['nama'],
                'kelas' => $pelajar['kelas'],
                'masa' => substr($masa, 0, 5),
                'status' => $status,
                'skor' => round($skorTerbaik, 3),
            ]);

        // ---------- Admin: daftar pelajar baharu dengan sampel wajah ----------
        case 'daftar':
            perlu_admin(true);
            $nama = trim($input['nama'] ?? '');
            $noMurid = trim($input['no_murid'] ?? '') ?: null;
            $kelas = strtoupper(trim($input['kelas'] ?? ''));
            $sampel = $input['sampel'] ?? [];
            if ($nama === '' || $kelas === '') balas(['ok' => false, 'mesej' => 'Nama dan kelas wajib diisi.'], 400);
            if (!is_array($sampel) || count($sampel) < 1) balas(['ok' => false, 'mesej' => 'Tiada sampel wajah.'], 400);

            $bersih = [];
            foreach ($sampel as $s) {
                $e = semak_embedding($s);
                if (!$e) balas(['ok' => false, 'mesej' => 'Sampel wajah tidak sah.'], 400);
                $bersih[] = normalkan($e);
            }

            if ($noMurid) {
                $c = db()->prepare('SELECT COUNT(*) FROM pelajar WHERE no_murid = ?');
                $c->execute([$noMurid]);
                if ($c->fetchColumn()) balas(['ok' => false, 'mesej' => 'No. murid sudah wujud.'], 409);
            }

            $pdo = db();
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO pelajar (nama, no_murid, kelas) VALUES (?,?,?)')->execute([$nama, $noMurid, $kelas]);
            $id = (int)$pdo->lastInsertId();
            $w = $pdo->prepare('INSERT INTO wajah (pelajar_id, embedding) VALUES (?,?)');
            foreach ($bersih as $e) $w->execute([$id, json_encode($e)]);
            $pdo->commit();
            balas(['ok' => true, 'id' => $id, 'mesej' => "$nama berjaya didaftarkan."]);

        // ---------- Admin: hapus pelajar ----------
        case 'hapus':
            perlu_admin(true);
            $id = (int)($input['id'] ?? 0);
            db()->prepare('DELETE FROM pelajar WHERE id = ?')->execute([$id]);
            balas(['ok' => true]);

        // ---------- Admin: statistik hari ini ----------
        case 'statistik':
            perlu_admin(true);
            $tarikh = date('Y-m-d');
            $jumlah = (int)db()->query('SELECT COUNT(*) FROM pelajar')->fetchColumn();
            $s = db()->prepare("SELECT
                    SUM(status='hadir') AS hadir, SUM(status='lewat') AS lewat
                FROM kehadiran WHERE tarikh = ?");
            $s->execute([$tarikh]);
            $r = $s->fetch();
            $t = db()->prepare('SELECT p.nama, p.kelas, k.masa, k.status FROM kehadiran k
                JOIN pelajar p ON p.id = k.pelajar_id WHERE k.tarikh = ? ORDER BY k.masa DESC LIMIT 12');
            $t->execute([$tarikh]);
            $hadir = (int)$r['hadir'];
            $lewat = (int)$r['lewat'];
            balas([
                'ok' => true,
                'jumlah' => $jumlah,
                'hadir' => $hadir,
                'lewat' => $lewat,
                'tidak_hadir' => max(0, $jumlah - $hadir - $lewat),
                'terkini' => $t->fetchAll(),
            ]);

        default:
            balas(['ok' => false, 'mesej' => 'Tindakan tidak dikenali.'], 404);
    }
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    balas(['ok' => false, 'mesej' => 'Ralat pelayan: ' . $e->getMessage()], 500);
}
